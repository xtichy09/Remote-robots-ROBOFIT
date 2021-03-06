#include <video_client/client.h>
#include <sys/ioctl.h>
#include <errno.h>
#include <signal.h>
#include <sys/socket.h>
#include <arpa/inet.h>
#include <sys/types.h>
#include <sys/stat.h>
#include <fcntl.h>
#include <syslog.h>
#include <netdb.h>
#include <errno.h>
#include <opencv2/opencv.hpp>
#include <boost/thread.hpp>
#include <boost/bind.hpp>
#include <string>


template<typename T>
  inline T ABS(T a)
  {
    return (a < 0 ? -a : a);
  }

template<typename T>
  inline T min(T a, T b)
  {
    return (a < b ? a : b);
  }

template<typename T>
  inline T max(T a, T b)
  {
    return (a > b ? a : b);
  }

template<typename T>
  inline T LENGTH_OF(T x)
  {
    return (sizeof(x) / sizeof(x[0]));
  }

namespace mjpeg_server
{

MJPEGServer::MJPEGServer(ros::NodeHandle& node) :
    node_(node), image_transport_(node), stop_requested_(false), www_folder_(NULL)
{
  ros::NodeHandle private_nh("~");
  private_nh.param("port", port_, 7000);
  header = "Connection: close\r\nServer: mjpeg_server\r\n"
      "Cache-Control: no-cache, no-store, must-revalidate, pre-check=0, post-check=0, max-age=0\r\n"
      "Pragma: no-cache\r\n";
  sd_len = 0;
}

MJPEGServer::~MJPEGServer()
{
  cleanUp();
}

void MJPEGServer::imageCallback(const sensor_msgs::ImageConstPtr& msg, const std::string& topic)
{

  ImageBuffer* image_buffer = getImageBuffer(topic);
  boost::unique_lock<boost::mutex> lock(image_buffer->mutex_);
  // copy image
  image_buffer->msg = *msg;
  // notify senders
  image_buffer->condition_.notify_all();
}

void MJPEGServer::splitString(const std::string& str, std::vector<std::string>& tokens, const std::string& delimiter)
{
  // Skip delimiters at beginning.
  std::string::size_type lastPos = str.find_first_not_of(delimiter, 0);
  // Find first "non-delimiter".
  std::string::size_type pos = str.find_first_of(delimiter, lastPos);

  while (std::string::npos != pos || std::string::npos != lastPos)
  {
    // Found a token, add it to the vector.
    tokens.push_back(str.substr(lastPos, pos - lastPos));
    // Skip delimiters.  Note the "not_of"
    lastPos = str.find_first_not_of(delimiter, pos);
    // Find next "non-delimiter"
    pos = str.find_first_of(delimiter, lastPos);
  }
}

int MJPEGServer::stringToInt(const std::string& str, const int default_value)
{
  int value;
  int res;
  if (str.length() == 0)
    return default_value;
  res = sscanf(str.c_str(), "%i", &value);
  if (res == 1)
    return value;
  return default_value;
}

void MJPEGServer::initIOBuffer(iobuffer *iobuf)
{
  memset(iobuf->buffer, 0, sizeof(iobuf->buffer));
  iobuf->level = 0;
}

void MJPEGServer::initRequest(request *req)
{
  req->type = A_UNKNOWN;
  req->type = A_UNKNOWN;
  req->parameter = NULL;
  req->client = NULL;
  req->credentials = NULL;
}

void MJPEGServer::freeRequest(request *req)
{
  if (req->parameter != NULL)
    free(req->parameter);
  if (req->client != NULL)
    free(req->client);
  if (req->credentials != NULL)
    free(req->credentials);
}

int MJPEGServer::readWithTimeout(int fd, iobuffer *iobuf, char *buffer, size_t len, int timeout)
{
  size_t copied = 0;
  int rc, i;
  fd_set fds;
  struct timeval tv;

  memset(buffer, 0, len);

  while ((copied < len))
  {
    i = min((size_t)iobuf->level, len - copied);
    memcpy(buffer + copied, iobuf->buffer + IO_BUFFER - iobuf->level, i);

    iobuf->level -= i;
    copied += i;
    if (copied >= len)
      return copied;

    /* select will return in case of timeout or new data arrived */
    tv.tv_sec = timeout;
    tv.tv_usec = 0;
    FD_ZERO(&fds);
    FD_SET(fd, &fds);
    if ((rc = select(fd + 1, &fds, NULL, NULL, &tv)) <= 0)
    {
      if (rc < 0)
        exit(EXIT_FAILURE);

      /* this must be a timeout */
      return copied;
    }

    initIOBuffer(iobuf);

    /*
     * there should be at least one byte, because select signalled it.
     * But: It may happen (very seldomly), that the socket gets closed remotly between
     * the select() and the following read. That is the reason for not relying
     * on reading at least one byte.
     */
    if ((iobuf->level = read(fd, &iobuf->buffer, IO_BUFFER)) <= 0)
    {
      /* an error occured */
      return -1;
    }

    /* align data to the end of the buffer if less than IO_BUFFER bytes were read */
    memmove(iobuf->buffer + (IO_BUFFER - iobuf->level), iobuf->buffer, iobuf->level);
  }

  return 0;
}

int MJPEGServer::readLineWithTimeout(int fd, iobuffer *iobuf, char *buffer, size_t len, int timeout)
{
  char c = '\0', *out = buffer;
  unsigned int i;

  memset(buffer, 0, len);

  for (i = 0; i < len && c != '\n'; i++)
  {
    if (readWithTimeout(fd, iobuf, &c, 1, timeout) <= 0)
    {
      /* timeout or error occured */
      return -1;
    }
    *out++ = c;
  }

  return i;
}

void MJPEGServer::decodeBase64(char *data)
{
  union
  {
    int i;
    char c[4];
  } buffer;

  char* ptr = data;
  unsigned int size = strlen(data);
  char* temp = new char[size];
  char* tempptr = temp;
  char t;

  for (buffer.i = 0, t = *ptr; ptr; ptr++)
  {
    if (t >= 'A' && t <= 'Z')
      t = t - 'A';
    else if (t >= 'a' && t <= 'z')
      t = t - 'a' + 26;
    else if (t >= '0' && t <= '9')
      t = t - '0' + 52;
    else if (t == '+')
      t = 62;
    else if (t == '/')
      t = 63;
    else
      continue;

    buffer.i = (buffer.i << 6) | t;

    if ((ptr - data + 1) % 4)
    {
      *tempptr++ = buffer.c[2];
      *tempptr++ = buffer.c[1];
      *tempptr++ = buffer.c[0];
      buffer.i = 0;
    }
  }
  *tempptr = '\0';
  strcpy(data, temp);
  delete temp;
}

int MJPEGServer::hexCharToInt(char in)
{
  if (in >= '0' && in <= '9')
    return in - '0';

  if (in >= 'a' && in <= 'f')
    return (in - 'a') + 10;

  if (in >= 'A' && in <= 'F')
    return (in - 'A') + 10;

  return -1;
}

int MJPEGServer::unescape(char *string)
{
  char *source = string, *destination = string;
  int src, dst, length = strlen(string), rc;

  /* iterate over the string */
  for (dst = 0, src = 0; src < length; src++)
  {

    /* is it an escape character? */
    if (source[src] != '%')
    {
      /* no, so just go to the next character */
      destination[dst] = source[src];
      dst++;
      continue;
    }

    /* yes, it is an escaped character */

    /* check if there are enough characters */
    if (src + 2 > length)
    {
      return -1;
      break;
    }

    /* perform replacement of %## with the corresponding character */
    if ((rc = hexCharToInt(source[src + 1])) == -1)
      return -1;
    destination[dst] = rc * 16;
    if ((rc = hexCharToInt(source[src + 2])) == -1)
      return -1;
    destination[dst] += rc;

    /* advance pointers, here is the reason why the resulting string is shorter */
    dst++;
    src += 2;
  }

  /* ensure the string is properly finished with a null-character */
  destination[dst] = '\0';

  return 0;
}

void MJPEGServer::sendError(int fd, int which, const char *message)
{
  char buffer[BUFFER_SIZE] = {0};

  if (which == 401)
  {
    sprintf(buffer, "HTTP/1.0 401 Unauthorized\r\n"
            "Content-type: text/plain\r\n"
            "%s"
            "WWW-Authenticate: Basic realm=\"MJPG-Streamer\"\r\n"
            "\r\n"
            "401: Not Authenticated!\r\n"
            "%s",
            header.c_str(), message);
  }
  else if (which == 404)
  {
    sprintf(buffer, "HTTP/1.0 404 Not Found\r\n"
            "Content-type: text/plain\r\n"
            "%s"
            "\r\n"
            "404: Not Found!\r\n"
            "%s",
            header.c_str(), message);
  }
  else if (which == 500)
  {
    sprintf(buffer, "HTTP/1.0 500 Internal Server Error\r\n"
            "Content-type: text/plain\r\n"
            "%s"
            "\r\n"
            "500: Internal Server Error!\r\n"
            "%s",
            header.c_str(), message);
  }
  else if (which == 400)
  {
    sprintf(buffer, "HTTP/1.0 400 Bad Request\r\n"
            "Content-type: text/plain\r\n"
            "%s"
            "\r\n"
            "400: Not Found!\r\n"
            "%s",
            header.c_str(), message);
  }
  else
  {
    sprintf(buffer, "HTTP/1.0 501 Not Implemented\r\n"
            "Content-type: text/plain\r\n"
            "%s"
            "\r\n"
            "501: Not Implemented!\r\n"
            "%s",
            header.c_str(), message);
  }

  if (write(fd, buffer, strlen(buffer)) < 0)
  {
    ROS_DEBUG("write failed, done anyway");
  }
}

void MJPEGServer::decodeParameter(const std::string& parameter, ParameterMap& parameter_map)
{
  std::vector<std::string> parameter_value_pairs;
  splitString(parameter, parameter_value_pairs, "?&");

  for (size_t i = 0; i < parameter_value_pairs.size(); i++)
  {
    std::vector<std::string> parameter_value;
    splitString(parameter_value_pairs[i], parameter_value, "=");
    if (parameter_value.size() == 1)
    {
      parameter_map.insert(std::make_pair(parameter_value[0], std::string("")));
    }
    else if (parameter_value.size() == 2)
    {
      parameter_map.insert(std::make_pair(parameter_value[0], parameter_value[1]));
    }
  }
}

ImageBuffer* MJPEGServer::getImageBuffer(const std::string& topic)
{
  boost::unique_lock<boost::mutex> lock(image_maps_mutex_);
  ImageSubscriberMap::iterator it = image_subscribers_.find(topic);
  if (it == image_subscribers_.end())
  {
    image_subscribers_[topic] = image_transport_.subscribe(topic, 1,
                                                           boost::bind(&MJPEGServer::imageCallback, this, _1, topic));
    image_buffers_[topic] = new ImageBuffer();
    ROS_INFO("Subscribing to topic %s", topic.c_str());
  }
  ImageBuffer* image_buffer = image_buffers_[topic];
  return image_buffer;
}

// rotate input image at 180 degrees
void MJPEGServer::invertImage(const cv::Mat& input, cv::Mat& output)
{

  cv::Mat_<cv::Vec3b>& input_img = (cv::Mat_<cv::Vec3b>&)input; //3 channel pointer to image
  cv::Mat_<cv::Vec3b>& output_img = (cv::Mat_<cv::Vec3b>&)output; //3 channel pointer to image
  cv::Size size = input.size();

  for (int j = 0; j < size.height; ++j)
    for (int i = 0; i < size.width; ++i)
    {
      //outputImage.imageData[size.height*size.width - (i + j*size.width) - 1] = inputImage.imageData[i + j*size.width];
      output_img(size.height - j - 1, size.width - i - 1) = input_img(j, i);
    }
  return;
}

void MJPEGServer::sendStream(int fd, const char *parameter)
{
  unsigned char *frame = NULL, *tmp = NULL;
  int frame_size = 0, max_frame_size = 0;
  int tenk = 10 * 1024;
  char buffer[BUFFER_SIZE] = {0};
  double timestamp;
  //sensor_msgs::CvBridge image_bridge;
  //sensor_msgs::cv_bridge image_bridge;
  cv_bridge::CvImage image_bridge;

  ROS_DEBUG("Decoding parameter");

  std::string params = parameter;

  ParameterMap parameter_map;
  decodeParameter(params, parameter_map);

  ParameterMap::iterator itp = parameter_map.find("topic");
  if (itp == parameter_map.end())
    return;

  std::string topic = itp->second;
  increaseSubscriberCount(topic);
  ImageBuffer* image_buffer = getImageBuffer(topic);

  ROS_DEBUG("preparing header");
  sprintf(buffer, "HTTP/1.0 200 OK\r\n"
          "%s"
          "Content-Type: multipart/x-mixed-replace;boundary=boundarydonotcross \r\n"
          "\r\n"
          "--boundarydonotcross \r\n",
          header.c_str());

  printf("BUFFER: %s", buffer);

  if (write(fd, buffer, strlen(buffer)) < 0)
  {
    free(frame);
    return;
  }

  printf("Headers send, sending stream now\n");

  while (!stop_requested_)
  {
    {
      /* wait for fresh frames */
      boost::unique_lock<boost::mutex> lock(image_buffer->mutex_);
      image_buffer->condition_.wait(lock);

      //IplImage* image;
      cv_bridge::CvImagePtr cv_msg;
      try
      {
        if (cv_msg = cv_bridge::toCvCopy(image_buffer->msg, "bgr8"))
        {
          ;    //image = image_bridge.toIpl();
        }
        else
        {
          ROS_ERROR("Unable to convert %s image to bgr8", image_buffer->msg.encoding.c_str());
          return;
        }
      }
      catch (...)
      {
        ROS_ERROR("Unable to convert %s image to ipl format", image_buffer->msg.encoding.c_str());
        return;
      }

      // encode image
      cv::Mat img = cv_msg->image;
      std::vector<uchar> encoded_buffer;
      std::vector<int> encode_params;

      // invert
      //int invert = 0;
      if (parameter_map.find("invert") != parameter_map.end())
      {
        cv::Mat cloned_image = img.clone();
        invertImage(cloned_image, img);
      }

      // quality
      int quality = 95;
      if (parameter_map.find("quality") != parameter_map.end())
      {
        quality = stringToInt(parameter_map["quality"]);
      }
      encode_params.push_back(CV_IMWRITE_JPEG_QUALITY);
      encode_params.push_back(quality);

      // resize image
      if (parameter_map.find("width") != parameter_map.end() && parameter_map.find("height") != parameter_map.end())
      {
        int width = stringToInt(parameter_map["width"]);
        int height = stringToInt(parameter_map["height"]);
        if (width > 0 && height > 0)
        {
          cv::Mat img_resized;
          cv::Size new_size(width, height);
          cv::resize(img, img_resized, new_size);
          cv::imencode(".jpeg", img_resized, encoded_buffer, encode_params);
        }
        else
        {
          cv::imencode(".jpeg", img, encoded_buffer, encode_params);
        }
      }
      else
      {
        cv::imencode(".jpeg", img, encoded_buffer, encode_params);
      }

      // copy encoded frame buffer
      frame_size = encoded_buffer.size();

      /* check if frame buffer is large enough, increase it if necessary */
      if (frame_size > max_frame_size)
      {
        printf("increasing frame buffer size to %d\n", frame_size);

        max_frame_size = frame_size + tenk;
        if ((tmp = (unsigned char*)realloc(frame, max_frame_size)) == NULL)
        {
          free(frame);
          sendError(fd, 500, "not enough memory");
          return;
        }
        frame = tmp;
      }

      /* copy v4l2_buffer timeval to user space */
      timestamp = ros::Time::now().toSec();

      memcpy(frame, &encoded_buffer[0], frame_size);
      ROS_DEBUG("got frame (size: %d kB)", frame_size / 1024);
    }

    /*
     * print the individual mimetype and the length
     * sending the content-length fixes random stream disruption observed
     * with firefox
     */
    sprintf(buffer, "Content-Type: image/jpeg\r\n"
            "Content-Length: %d\r\n"
            "X-Timestamp: %.06lf\r\n"
            "\r\n",
            frame_size, (double)timestamp);
    //printf("Sending intemdiate header\n%s", buffer);
    if (write(fd, buffer, strlen(buffer)) < 0)
      break;

    //printf("Sending frame\n");
    if (write(fd, frame, frame_size) < 0)
      break;

    //printf("Sending boundary\n%s", buffer);
    sprintf(buffer, "\r\n--boundarydonotcross \r\n");
    if (write(fd, buffer, strlen(buffer)) < 0)
      break;
  }

  free(frame);
  decreaseSubscriberCount(topic);
  unregisterSubscriberIfPossible(topic);

}

void MJPEGServer::sendSnapshot(int fd, const char *parameter)
{
;
}

void MJPEGServer::makeImageBuffer(int fd){

    //Nacitat obrazok
    //Poskladat atd
    printf("MakeImageBuffer");
    return;
}

void MJPEGServer::client(int fd)
{
  int cnt;
  char buffer[BUFFER_SIZE] = {0}, *pb = buffer;
  char outbuffer[BUFFER_SIZE] = {0};
  iobuffer iobuf;
  request req;
  printf("CLIENT: Pustam klienta\n");

  /* initializes the structures */
  initIOBuffer(&iobuf);
  initRequest(&req);

  /* What does the client want to receive? Read the request. */
  printf("CLIENT: Cekam na hlavicku\n");
  memset(buffer, 0, sizeof(buffer));
  if ((cnt = readLineWithTimeout(fd, &iobuf, buffer, sizeof(buffer) - 1, 5)) == -1)
  {
    close(fd);
    return;
  }

  printf("PRINTING BUFFER: %s\n", buffer);

  /* determine what to deliver */
  if (strstr(buffer, "GET /?") != NULL)
  {
    req.type = A_STREAM;

    /* advance by the length of known string */
    if ((pb = strstr(buffer, "GET /")) == NULL)
    {
      ROS_DEBUG("HTTP request seems to be malformed");
      sendError(fd, 400, "Malformed HTTP request");
      close(fd);
      return;
    }
    pb += strlen("GET /"); // a pb points to the string after the first & after command
    int len = min(max((int)strspn(pb, "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ._/-1234567890?="), 0), 100);
    req.parameter = (char*)malloc(len + 1);
    if (req.parameter == NULL)
    {
      exit(EXIT_FAILURE);
    }
    memset(req.parameter, 0, len + 1);
    strncpy(req.parameter, pb, len);

    printf("Requested image topic[%d]: \"%s\"\n", len, req.parameter);
  }
  else if (strstr(buffer, "GET /stream?") != NULL)
  {
    req.type = A_STREAM;
    printf("TADY\n");

    /* advance by the length of known string */
    if ((pb = strstr(buffer, "GET /stream")) == NULL)
    {
      ROS_DEBUG("HTTP request seems to be malformed");
      sendError(fd, 400, "Malformed HTTP request");
      close(fd);
      return;
    }
    pb += strlen("GET /stream"); // a pb points to the string after the first & after command
    int len = min(max((int)strspn(pb, "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ._/-1234567890?="), 0), 100);
    req.parameter = (char*)malloc(len + 1);
    if (req.parameter == NULL)
    {
      exit(EXIT_FAILURE);
    }
    memset(req.parameter, 0, len + 1);
    strncpy(req.parameter, pb, len);

    printf("requested image topic[%d]: \"%s\"\n", len, req.parameter);
  }
  else if (strstr(buffer, "GET /snapshot?") != NULL)
  {
    req.type = A_SNAPSHOT;

    /* advance by the length of known string */
    if ((pb = strstr(buffer, "GET /snapshot")) == NULL)
    {
      ROS_DEBUG("HTTP request seems to be malformed");
      sendError(fd, 400, "Malformed HTTP request");
      close(fd);
      return;
    }
    pb += strlen("GET /snapshot"); // a pb points to the string after the first & after command
    int len = min(max((int)strspn(pb, "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ._/-1234567890?="), 0), 100);
    req.parameter = (char*)malloc(len + 1);
    if (req.parameter == NULL)
    {
      exit(EXIT_FAILURE);
    }
    memset(req.parameter, 0, len + 1);
    strncpy(req.parameter, pb, len);

    ROS_DEBUG("requested image topic[%d]: \"%s\"", len, req.parameter);
  }else if (strstr(buffer, "MJPEG:CLIENT") != NULL){
    printf("Pripojenie MJPEG_CLIENT-a\n");
    sprintf(outbuffer, "MJPEG:SERVER|ACCEPT\r\n");
    write(fd, outbuffer, strlen(outbuffer));
    makeImageBuffer(fd);
  }

  /*
   * parse the rest of the HTTP-request
   * the end of the request-header is marked by a single, empty line with "\r\n"
   */
  do
  {
    memset(buffer, 0, sizeof(buffer));

    if ((cnt = readLineWithTimeout(fd, &iobuf, buffer, sizeof(buffer) - 1, 5)) == -1)
    {
      freeRequest(&req);
      close(fd);
      return;
    }

    if (strstr(buffer, "User-Agent: ") != NULL)
    {
      req.client = strdup(buffer + strlen("User-Agent: "));
    }
    else if (strstr(buffer, "Authorization: Basic ") != NULL)
    {
      req.credentials = strdup(buffer + strlen("Authorization: Basic "));
      decodeBase64(req.credentials);
      ROS_DEBUG("username:password: %s", req.credentials);
    }

  } while (cnt > 2 && !(buffer[0] == '\r' && buffer[1] == '\n'));

  /* now it's time to answer */
  switch (req.type)
  {
    case A_STREAM:
    {
      printf("TIME TO SENDING STREAM\n");
      sendStream(fd, req.parameter);
      break;
    }
    case A_SNAPSHOT:
    {
      ROS_DEBUG("Request for snapshot");
      sendSnapshot(fd, req.parameter);
      break;
    }
    default:
      printf("Jo jede to nejak to osefuj request\n");
  }

  close(fd);
  freeRequest(&req);
  ROS_INFO("Disconnecting HTTP client");
  return;
}

void MJPEGServer::execute(char* ip, int port)
{

  printf("Connecting to mjpeg_server\n");

  int sockfd, portno, n;
  struct sockaddr_in serv_addr;
  struct hostent *server;
  char buffer[BUFFER_SIZE];
  portno = port;
  sockfd = socket(AF_INET, SOCK_STREAM, 0);
  if (sockfd < 0) 
    printf("ERROR opening socket\n");
  
  server = gethostbyname(ip);
  if (server == NULL) {
        printf("ERROR, no such host\n");
        exit(0);
    }
  bzero((char *) &serv_addr, sizeof(serv_addr));
  serv_addr.sin_family = AF_INET;
  bcopy((char *)server->h_addr, (char *)&serv_addr.sin_addr.s_addr, server->h_length);
  serv_addr.sin_port = htons(portno);

  if (connect(sockfd,(struct sockaddr *) &serv_addr,sizeof(serv_addr)) < 0) 
        printf("ERROR connecting\n");

  bzero(buffer, BUFFER_SIZE);
  sprintf(buffer, "MJPEG:CLIENT|REGISTRATION\r\n");
  n = write(sockfd,buffer,strlen(buffer));
  if (n < 0) 
    printf("ERROR writing to socket\n");
  

  boost::thread stream(boost::bind(&MJPEGServer::client, this, sockfd));
  stream.detach();

  //bzero(buffer, BUFFER_SIZE);  
  //n = read(sockfd,buffer,BUFFER_SIZE);
  //if (n < 0) 
  //  printf("ERROR reading from socket\n");
  //printf("%s\n",buffer);
}

void MJPEGServer::cleanUp()
{
  ROS_INFO("cleaning up ressources allocated by server thread");

  for (int i = 0; i < MAX_NUM_SOCKETS; i++)
    close(sd[i]);
}

void MJPEGServer::spin(char* ip, int port)
{
  boost::thread t(boost::bind(&MJPEGServer::execute, this, ip, port));
  t.detach();
  ros::spin();
  ROS_INFO("stop requested");
  stop();
}

void MJPEGServer::stop()
{
  stop_requested_ = true;
}

void MJPEGServer::decreaseSubscriberCount(const std::string topic)
{
  boost::unique_lock<boost::mutex> lock(image_maps_mutex_);
  ImageSubscriberCountMap::iterator it = image_subscribers_count_.find(topic);
  if (it != image_subscribers_count_.end())
  {
    if (image_subscribers_count_[topic] == 1) {
      image_subscribers_count_.erase(it);
      ROS_INFO("no subscribers for %s", topic.c_str());
    }
    else if (image_subscribers_count_[topic] > 0) {
      image_subscribers_count_[topic] = image_subscribers_count_[topic] - 1;
      ROS_INFO("%lu subscribers for %s", image_subscribers_count_[topic], topic.c_str());
    }
  }
  else
  {
    ROS_INFO("no subscribers counter for %s", topic.c_str());
  }
}

void MJPEGServer::increaseSubscriberCount(const std::string topic)
{
  boost::unique_lock<boost::mutex> lock(image_maps_mutex_);
  ImageSubscriberCountMap::iterator it = image_subscribers_count_.find(topic);
  if (it == image_subscribers_count_.end())
  {
    image_subscribers_count_.insert(ImageSubscriberCountMap::value_type(topic, 1));
  }
  else {
    image_subscribers_count_[topic] = image_subscribers_count_[topic] + 1;
  }
  ROS_INFO("%lu subscribers for %s", image_subscribers_count_[topic], topic.c_str());
}

void MJPEGServer::unregisterSubscriberIfPossible(const std::string topic)
{
  boost::unique_lock<boost::mutex> lock(image_maps_mutex_);
  ImageSubscriberCountMap::iterator it = image_subscribers_count_.find(topic);
  if (it == image_subscribers_count_.end() ||
      image_subscribers_count_[topic] == 0)
  {
    ImageSubscriberMap::iterator sub_it = image_subscribers_.find(topic);
    if (sub_it != image_subscribers_.end())
    {
      ROS_INFO("Unsubscribing from %s", topic.c_str());
      image_subscribers_.erase(sub_it);
    }
  }
}
}

int main(int argc, char** argv)
{
  ros::init(argc, argv, "mjpeg_client");

  //if(argc == 1){
    ros::NodeHandle nh;
    mjpeg_server::MJPEGServer server(nh);
    server.spin(argv[1], atoi(argv[2]));
    //server.spin(7000);
  //}
  //else
  //printf("You must chose port\n");
  

  return (0);
}


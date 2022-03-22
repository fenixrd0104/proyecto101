<?php

namespace Wechat;

use Wechat\Lib\Common;
use Wechat\Lib\Tools;

/**
 * WeChat media material management
 *
 * @author Anyon <zoujingli@qq.com>
 * @date 2016/10/26 14:47
 */
class WechatMedia extends Common {

    const UPLOAD_MEDIA_URL = 'http://file.api.weixin.qq.com/cgi-bin';
    const MEDIA_UPLOAD_URL = '/media/upload?';
    const MEDIA_UPLOADIMG_URL = '/media/uploadimg?'; //Image upload interface
    const MEDIA_GET_URL = '/media/get?';
    const MEDIA_VIDEO_UPLOAD = '/media/uploadvideo?';
    const MEDIA_FOREVER_UPLOAD_URL = '/material/add_material?';
    const MEDIA_FOREVER_NEWS_UPLOAD_URL = '/material/add_news?';
    const MEDIA_FOREVER_NEWS_UPDATE_URL = '/material/update_news?';
    const MEDIA_FOREVER_GET_URL = '/material/get_material?';
    const MEDIA_FOREVER_DEL_URL = '/material/del_material?';
    const MEDIA_FOREVER_COUNT_URL = '/material/get_materialcount?';
    const MEDIA_FOREVER_BATCHGET_URL = '/material/batchget_material?';
    const MEDIA_UPLOADNEWS_URL = '/media/uploadnews?';

    /**
     * Upload temporary material, valid for 3 days (the certified subscription number is available)
     * Note: when uploading large files, you may need to call set_time_limit(0) first to avoid timeouts
     * Note: The key value of the array is arbitrary, but the file name must be preceded by @, use single quotes to avoid escaping the local path slashes
     * Note: The media_id of temporary assets is reusable!
     * @param array $data {"media":'@Path\filename.jpg'}
     * @param string $type type: image:image voice:voice video:video thumbnail:thumb
     * @return bool|array
     */
    public function uploadMedia($data, $type) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        //The original upload multimedia file interface uses the self::UPLOAD_MEDIA_URL prefix
        $result = Tools::httpPost(self::API_URL_PREFIX . self::MEDIA_UPLOAD_URL . "access_token={$this->access_token}" . '&type=' . $type, $data, true);
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || !empty($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return $this->checkRetry(__FUNCTION__, func_get_args());
            }
            return $json;
        }
        return false;
    }

    /**
     * Get temporary materials (certified subscription numbers are available)
     * @param string $media_id media file id
     * @param bool $is_video is a video file, the default is no
     * @return bool|array
     */
    public function getMedia($media_id, $is_video = false) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        //The original upload multimedia file interface uses the self::UPLOAD_MEDIA_URL prefix
        //If the material to be obtained is a video file, the https protocol cannot be used and must be replaced with the http protocol
        $url_prefix = $is_video ? str_replace('https', 'http', self::API_URL_PREFIX) : self::API_URL_PREFIX;
        $result = Tools::httpGet($url_prefix . self::MEDIA_GET_URL . "access_token={$this->access_token}" . '&media_id=' . $media_id);
        if ($result) {
            if (is_string($result)) {
                $json = json_decode($result, true);
                if (isset($json['errcode'])) {
                    $this->errCode = $json['errcode'];
                    $this->errMsg = $json['errmsg'];
                    return $this->checkRetry(__FUNCTION__, func_get_args());
                }
            }
            return $result;
        }
        return false;
    }

    /**
     * Upload pictures. The pictures uploaded by this interface do not occupy the limit of 5000 pictures in the material library of the official account. The image only supports jpg/png format, and the size must be less than 1MB. (Authenticated subscription numbers are available)
     * Note: when uploading large files, you may need to call set_time_limit(0) first to avoid timeouts
     * Note: The key value of the array is arbitrary, but the file name must be preceded by @, use single quotes to avoid escaping the local path slashes
     * @param array $data {"media":'@Path\filename.jpg'}
     * @return bool|array
     */
    public function uploadImg($data) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        /* The original upload multimedia file interface uses the self::UPLOAD_MEDIA_URL prefix */
        $result = Tools::httpPost(self::API_URL_PREFIX . self::MEDIA_UPLOADIMG_URL . "access_token={$this->access_token}", $data, true);
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || !empty($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return $this->checkRetry(__FUNCTION__, func_get_args());
            }
            return $json;
        }
        return false;
    }

    /**
     * Upload permanent material (certified subscription number is available)
     * The newly added permanent material can also be seen in the material management module of the official website of the public platform
     * Note: when uploading large files, you may need to call set_time_limit(0) first to avoid timeouts
     * Note: The key value of the array is arbitrary, but the file name must be preceded by @, use single quotes to avoid escaping the local path slashes
     * @param array $data {"media":'@Path\filename.jpg'}
     * @param string $type type: image:image voice:voice video:video thumbnail:thumb
     * @param bool $is_video is a video file, the default is no
     * @param array $video_info video information array, non-video material does not need to provide array('title'=>'video title','introduction'=>'description')
     * @return bool|array
     */
    public function uploadForeverMedia($data, $type, $is_video = false, $video_info = array()) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        if ($is_video) {
            $data['description'] = Tools::json_encode($video_info);
        }
        $result = Tools::httpPost(self::API_URL_PREFIX . self::MEDIA_FOREVER_UPLOAD_URL . "access_token={$this->access_token}" . '&type=' . $type, $data, true);
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || !empty($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return $this->checkRetry(__FUNCTION__, func_get_args());
            }
            return $json;
        }
        return false;
    }

    /**
     * Upload permanent graphic material (certified subscription number is available)
     * The newly added permanent material can also be seen in the material management module of the official website of the public platform
     * @param array $data message structure {"articles":[{...}]}
     * @return bool|array
     */
    public function uploadForeverArticles($data) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $result = Tools::httpPost(self::API_URL_PREFIX . self::MEDIA_FOREVER_NEWS_UPLOAD_URL . "access_token={$this->access_token}", Tools::json_encode($data));
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || !empty($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return $this->checkRetry(__FUNCTION__, func_get_args());
            }
            return $json;
        }
        return false;
    }

    /**
     * Modify permanent graphic materials (certified subscription numbers are available)
     * Permanent material can also be seen in the material management module of the official website of the public platform
     * @param string $media_id Image id
     * @param array $data message structure {"articles":[{...}]}
     * @param int $index The position of the updated article in the graphic material, the first one is 0, only used for multiple graphic materials
     * @return bool|array
     */
    public function updateForeverArticles($media_id, $data, $index = 0) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        if (!isset($data['media_id'])) {
            $data['media_id'] = $media_id;
        }
        if (!isset($data['index'])) {
            $data['index'] = $index;
        }
        $result = Tools::httpPost(self::API_URL_PREFIX . self::MEDIA_FOREVER_NEWS_UPDATE_URL . "access_token={$this->access_token}", Tools::json_encode($data));
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || !empty($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return $this->checkRetry(__FUNCTION__, func_get_args());
            }
            return $json;
        }
        return false;
    }

    /**
     * Get permanent materials (certified subscription numbers are available)
     * Returns the text message array or binary data, returns false on failure
     * @param string $media_id media file id
     * @param bool $is_video is a video file, the default is no
     * @return bool|array|raw data
     */
    public function getForeverMedia($media_id, $is_video = false) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $data = array('media_id' => $media_id);
        //#TODO I'm not sure whether this interface needs to let the video file go through the http protocol
        //If the material to be obtained is a video file, the https protocol cannot be used and must be replaced with the http protocol
        //$url_prefix = $is_video?str_replace('https','http',self::API_URL_PREFIX):self::API_URL_PREFIX;
        $result = Tools::httpPost(self::API_URL_PREFIX . self::MEDIA_FOREVER_GET_URL . "access_token={$this->access_token}", Tools::json_encode($data));
        if ($result) {
            if (is_string($result)) {
                $json = json_decode($result, true);
                if ($json) {
                    if (isset($json['errcode'])) {
                        $this->errCode = $json['errcode'];
                        $this->errMsg = $json['errmsg'];
                        return $this->checkRetry(__FUNCTION__, func_get_args());
                    }
                    return $json;
                } else {
                    return $result;
                }
            }
            return $result;
        }
        return false;
    }

    /**
     * Delete permanent material (certified subscription number is available)
     * @param string $media_id media file id
     * @return bool
     */
    public function delForeverMedia($media_id) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $data = array('media_id' => $media_id);
        $result = Tools::httpPost(self::API_URL_PREFIX . self::MEDIA_FOREVER_DEL_URL . "access_token={$this->access_token}", Tools::json_encode($data));
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || !empty($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return $this->checkRetry(__FUNCTION__, func_get_args());
            }
            return true;
        }
        return false;
    }

    /**
    * Get permanent material list (certified subscription number is available)
     * @param string $type The type of material, image (image), video (video), voice (voice), graphic (news)
     * @param int $offset The offset position of all materials, 0 means from the first material
     * @param int $count Returns the number of materials, between 1 and 20
     * @return bool|array
     * Return array format:
     *array(
     * 'total_count'=>0, //The total number of materials of this type
     * 'item_count'=>0, //The number of materials obtained by this call
     * 'item'=>array() //Material list array, please refer to official documentation for content definition
     * )
     */
    public function getForeverList($type, $offset, $count) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $data = array(
            'type'   => $type,
            'offset' => $offset,
            'count'  => $count,
        );
        $result = Tools::httpPost(self::API_URL_PREFIX . self::MEDIA_FOREVER_BATCHGET_URL . "access_token={$this->access_token}", Tools::json_encode($data));
        if ($result) {
            $json = json_decode($result, true);
            if (isset($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return $this->checkRetry(__FUNCTION__, func_get_args());
            }
            return $json;
        }
        return false;
    }

    /**
    * Get the total number of permanent materials (certified subscription numbers are available)
     * @return bool|array
     * Return array format:
     *array(
     * 'voice_count'=>0, //Total number of voices
     * 'video_count'=>0, //Total number of videos
     * 'image_count'=>0, //Total number of images
     * 'news_count'=>0 //Total number of pictures and texts
     * )
     */
    public function getForeverCount() {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $result = Tools::httpGet(self::API_URL_PREFIX . self::MEDIA_FOREVER_COUNT_URL . "access_token={$this->access_token}");
        if ($result) {
            $json = json_decode($result, true);
            if (isset($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return $this->checkRetry(__FUNCTION__, func_get_args());
            }
            return $json;
        }
        return false;
    }

    /**
     * Upload graphic message material for group sending (authenticated subscription number is available)
     * @param array $data 消息结构{"articles":[{...}]}
     * @return bool|array
     */
    public function uploadArticles($data) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $result = Tools::httpPost(self::API_URL_PREFIX . self::MEDIA_UPLOADNEWS_URL . "access_token={$this->access_token}", Tools::json_encode($data));
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || !empty($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return $this->checkRetry(__FUNCTION__, func_get_args());
            }
            return $json;
        }
        return false;
    }

    /**
     * Upload video material (certified subscription number is available)
     * @param array $data message structure
     * {
     * "media_id"=>"", //MediaId obtained by uploading media interface
     * "title"=>"TITLE", //video title
     * "description"=>"Description" //Video description
     * }
     * @return bool|array
     * {
     *     "type":"video",
     *     "media_id":"mediaid",
     *     "created_at":1398848981
     *  }
     */
    public function uploadMpVideo($data) {
        if (!$this->access_token && !$this->getAccessToken()) {
            return false;
        }
        $result = Tools::httpPost(self::UPLOAD_MEDIA_URL . self::MEDIA_VIDEO_UPLOAD . "access_token={$this->access_token}", Tools::json_encode($data));
        if ($result) {
            $json = json_decode($result, true);
            if (!$json || !empty($json['errcode'])) {
                $this->errCode = $json['errcode'];
                $this->errMsg = $json['errmsg'];
                return $this->checkRetry(__FUNCTION__, func_get_args());
            }
            return $json;
        }
        return false;
    }

}

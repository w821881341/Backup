<?php

if (!defined('IN_DISCUZ')) {
    exit('Access Denied');
}

class BaiduPostSchema
{

    protected $_postContent;
    protected $_createdTime;
    protected $_viewAuthority;
    protected $_isHost;
    protected $_postSequenceNumber;
    protected $_attachment = array();
    protected $_authorIcon; //用户头像
    protected $_author; //作者

    public function setAuthor($author)
    {
        $this->_author = $author;
    }

    public function setAuthorIcon($uid, $size = "small")
    {
        $authorIconUrl = baidu_get_avatar($uid, $size);
        $this->_authorIcon = $authorIconUrl;
    }

    public function setPostContent($content)
    {
        $this->_postContent = baidu_strip_invalid_xml(diconv(trim($content), CHARSET, 'utf-8'));
    }

    public function setCreatedTime($time)
    {
        $time = trim($time);
        $this->_createdTime = preg_match('#^\d+$#', $time) ? baidu_date_format($time) : $time;
    }

    public function setViewAuthority($string)
    {
        $this->_viewAuthority = diconv(trim($string), CHARSET, 'utf-8');
    }

    public function setIsHost($ishost)
    {
        $this->_isHost = intval($ishost);
    }

    public function setPostSequenceNumber($number)
    {
        $this->_postSequenceNumber = intval($number);
    }

    public function addAttachment(BaiduAttachmentSchema $attach)
    {
        $this->_attachment[] = $attach;
    }

    public function toXml()
    {
        $attach_xml = '';
        foreach ($this->_attachment as $x) {
            $attach_xml .= $x->toXml();
        }
        return
                "<post>\n" .
                "<postContent><![CDATA[{$this->_postContent}]]></postContent>\n" .
                "<createdTime>{$this->_createdTime}</createdTime>\n" .
                "<viewAuthority><![CDATA[{$this->_viewAuthority}]]></viewAuthority>\n" .
                "<author><![CDATA[{$this->_author}]]></author>\n" .
                "<authorIcon><![CDATA[{$this->_authorIcon}]]></authorIcon>\n" .
                //"<isHost>{$this->_isHost}</isHost>\n" .
                "<postSequenceNumber>{$this->_postSequenceNumber}</postSequenceNumber>\n" .
                $attach_xml .
                "</post>\n";
    }

}

class BaiduAttachmentSchema
{

    protected $_attachmentName; //<!-- attachmentName: 附件名称，含文件格式（txt、rar、mp3等），最少出现1次 最多出现1次，类型为字符串 -->
    protected $_attachmentUrl; //<!-- attachmentUrl: 附件访问地址，，最少出现1次 最多出现1次，类型为URL地址 -->
    protected $_attachmentSize; //<!-- attachmentSize: 附件大小，K、M、B等，最少出现0次 最多出现1次，类型为字符串 -->
    protected $_attachmentDownloadAuthority; //<!-- attachmentDownloadAuthority: 附件访问权限，0非登录(游客可访问）、1登录、2登录+回帖、3登录+积分、4登录+用户等级，最少出现0次 最多出现1次，类型为整数 -->
    protected $_attachmentDownloadCount; //<!-- attachmentDownloadCount: 附件下载次数，，最少出现0次 最多出现1次，类型为整数 -->
    protected $_attachmentType;

    public function setName($name)
    {
        $this->_attachmentName = baidu_strip_invalid_xml(diconv(trim($name), CHARSET, 'utf-8'));
        $this->_setType(strtolower(fileext($this->_attachmentName)));
    }

    public function setUrl($url)
    {
        $this->_attachmentUrl = baidu_encode_url(trim($url));
    }

    public function setSize($size)
    {
        if (preg_match('#^\d+$#', $size)) {
            if ($size > 1024 * 1024) {
                $size = round($size / 1024 / 1024, 1) . 'M';
            } elseif ($size > 1024) {
                $size = round($size / 1024, 1) . 'K';
            } else {
                $size .= 'B';
            }
        }
        $this->_attachmentSize = $size;
    }

    public function setDownloadAuthority($authority)
    {
        $this->_attachmentDownloadAuthority = intval($authority);
    }

    public function setDownloadCount($count)
    {
        $this->_attachmentDownloadCount = intval($count);
    }

    protected function _setType($extname)
    {
        /**
         * 0=普通
         * 1=图片
         * 2=音频
         * 3=视频
         * 4=下载
         */
        if (preg_match("/bittorrent|^torrent/", $extname)) {
            $typeid = 4;
        } elseif (preg_match("/pdf|^pdf/", $extname)) {
            $typeid = 4;
        } elseif (preg_match("/image|^(jpg|gif|png|bmp)/", $extname)) {
            $typeid = 1;
        } elseif (preg_match("/flash|^(swf|fla|flv|swi)/", $extname)) {
            $typeid = 3;
        } elseif (preg_match("/audio|video|^(wav|mid|mp3|m3u|wma|asf|asx|vqf|mpg|mpeg|avi|wmv)/", $extname)) {
            $typeid = 2;
        } elseif (preg_match("/real|^(ra|rm|rv)/", $extname)) {
            $typeid = 3;
        } elseif (preg_match("/htm|^(php|js|pl|cgi|asp)/", $extname)) {
            $typeid = 0;
        } elseif (preg_match("/text|^(txt|rtf|wri|chm)/", $extname)) {
            $typeid = 0;
        } elseif (preg_match("/word|powerpoint|^(doc|ppt)/", $extname)) {
            $typeid = 0;
        } elseif (preg_match("/^rar/", $extname)) {
            $typeid = 4;
        } elseif (preg_match("/compressed|^(zip|arj|arc|cab|lzh|lha|tar|gz)/", $extname)) {
            $typeid = 4;
        } elseif (preg_match("/octet-stream|^(exe|com|bat|dll)/", $extname)) {
            $typeid = 4;
        } else {
            $typeid = 0;
        }
        $this->_attachmentType = $typeid;
    }

    public function toXml()
    {
        return
                "<attachment>\n" .
                "<attachmentName><![CDATA[{$this->_attachmentName}]]></attachmentName>\n" .
                "<attachmentUrl><![CDATA[{$this->_attachmentUrl}]]></attachmentUrl>\n" .
                "<attachmentSize><![CDATA[{$this->_attachmentSize}]]></attachmentSize>\n" .
                "<attachmentDownloadAuthority>{$this->_attachmentDownloadAuthority}</attachmentDownloadAuthority>\n" .
                "<attachmentDownloadCount>{$this->_attachmentDownloadCount}</attachmentDownloadCount>\n" .
                "<attachmentType><![CDATA[{$this->_attachmentType}]]></attachmentType>\n" .
                "</attachment>\n";
    }

}

class BaiduThreadSchema
{

    protected $_forumName;  //版块名称
    protected $_threadUrl;  //链接地址
    protected $_threadTitle;  //主题title
    protected $_post = array();  //帖子
    protected $_replyCount;  //回复数
    protected $_viewCount;  //浏览数
    protected $_lastReplyTime;  //最后回复时间
    protected $_forumIcon; //版块图片
    protected $_moderators; //版主
    protected $_authorIcon; //用户头像
    protected $_author; //作者
    protected $_supportCount = null; //主题 支持数
    protected $_opposeCount = null; //主题 反对数
    protected $_isDigest; //是否精华
    protected $_stickyLevel; //置顶，0为不置顶，1为版面，2为分区，3为全局
    protected $_lastReplier; //最后回复人
    protected $_favorCount; //收藏数
    protected $_sharedCount; //分享数

    public function setFavorCount($favtimes)
    {
        $this->_favorCount = $favtimes;
    }

    public function setSharedCount($sharetimes)
    {
        $this->_sharedCount = $sharetimes;
    }

    public function setLastReplier($name)
    {
        $this->_lastReplier = $name;
    }

    public function setStickyLevel($level)
    {
        $this->_stickyLevel = $level;
    }

    public function setIsDigest($digest)
    {
        $this->_isDigest = $digest;
    }

    public function setSupportCount($count)
    {
        global $_G;
        $recommendthread = $_G['setting']['recommendthread'];
        if (isset($recommendthread['status'])) {
            $this->_supportCount = $count;
        }
    }

    public function setOpposeCount($count)
    {
        global $_G;
        $recommendthread = $_G['setting']['recommendthread'];
        if (isset($recommendthread['status'])) {
            $this->_opposeCount = $count;
        }
    }

    public function setAuthor($author)
    {
        $this->_author = $author;
    }

    public function setForumIcon($forumIcon)
    {
        global $_G;
        if ($forumIcon) {
            $url = $_G['siteurl'] . $_G['setting']['attachurl'] . "common/" . $forumIcon;
        } else {
            $url = $_G['siteurl'] . $_G['style']['imgdir'] . "/forum.gif";
        }
        $this->_forumIcon = $url;
    }

    public function setModerators($moderators)
    {
        $this->_moderators = $moderators;
    }

    public function setAuthorIcon($uid, $size = "small")
    {
        $authorIconUrl = baidu_get_avatar($uid, $size);
        $this->_authorIcon = $authorIconUrl;
    }

    public function setForumName($name)
    {
        $this->_forumName = baidu_strip_invalid_xml(diconv(trim($name), CHARSET, 'utf-8'));
    }

    public function setThreadUrl($url)
    {
        $this->_threadUrl = trim($url);
    }

    public function setThreadTitle($title)
    {
        $this->_threadTitle = baidu_strip_invalid_xml(diconv(trim($title), CHARSET, 'utf-8'));
    }

    public function setReplyCount($count)
    {
        $this->_replyCount = intval($count);
    }

    public function setViewCount($count)
    {
        $this->_viewCount = intval($count);
    }

    public function setLastReplyTime($time)
    {
        $time = trim($time);
        $this->_lastReplyTime = preg_match('#^\d+$#', $time) ? baidu_date_format($time) : $time;
    }

    public function addPost(BaiduPostSchema $post)
    {
        $this->_post[] = $post;
    }

    public function toXml()
    {
        $xml = "<url>\n" .
                "<loc><![CDATA[{$this->_threadUrl}]]></loc>\n" .
                "<lastmod><![CDATA[{$this->_lastReplyTime}]]></lastmod>\n" .
                "<data>\n" .
                "<thread>\n" .
                "<threadUrl><![CDATA[{$this->_threadUrl}]]></threadUrl>\n" .
                "<author><![CDATA[{$this->_author}]]></author>\n" .
                "<authorIcon><![CDATA[{$this->_authorIcon}]]></authorIcon>\n" .
                "<threadTitle><![CDATA[{$this->_threadTitle}]]></threadTitle>\n" .
                "<stickyLevel><![CDATA[{$this->_stickyLevel}]]></stickyLevel>\n" .
                "<isDigest><![CDATA[{$this->_isDigest}]]></isDigest>\n";
        foreach ($this->_post as $post) {
            $xml .= $post->toXml();
        }
        $xml .= "<replyCount>{$this->_replyCount}</replyCount>\n" .
                "<viewCount>{$this->_viewCount}</viewCount>\n" .
                "<lastReplier>\n" .
                "<accountName>{$this->_lastReplier}</accountName>\n" .
                "</lastReplier>\n" .
                "<lastReplyTime>{$this->_lastReplyTime}</lastReplyTime>\n" .
                "<favorCount>{$this->_favorCount}</favorCount>\n" .
                "<sharedCount>{$this->_sharedCount}</sharedCount>\n";
        if ($this->_supportCount != null && $this->_opposeCount != null) {
            $xml .= "<supportCount>{$this->_supportCount}</supportCount>\n" .
                    "<opposeCount>{$this->_opposeCount}</opposeCount>\n";
        }
        $xml .= "<forumIn>\n" .
                "<forumName><![CDATA[{$this->_forumName}]]></forumName>\n" .
                "<forumIcon><![CDATA[{$this->_forumIcon}]]></forumIcon>\n" .
                "<boardMasterID><![CDATA[{$this->_moderators}]]></boardMasterID>\n" .
                "</forumIn>\n" .
                "</thread>\n" .
                "</data>\n" .
                "</url>\n";
        return $xml;
    }

    public function toDeleteXml()
    {
        $xml = "<url><loc><![CDATA[{$this->_threadUrl}]]></loc></url>";
        return $xml;
    }

    public function toSitemapXml()
    {
        $date = date('Y-m-d');
        return "<url><loc><![CDATA[{$this->_threadUrl}]]></loc><lastmod>{$date}</lastmod></url>";
    }

}

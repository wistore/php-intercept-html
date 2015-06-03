<?php

/**
 * Created by PhpStorm.
 * author: kevin
 * Date: 15/6/3
 * Time: 下午1:25
 */
class Html
{

    /**
     *清理HTML文本，补全缺失标签，并清除不安全标签
     *
     *     HTML::clean('<script>xss</script><b>H</b>ello <em>World');
     *     //替换成 "<b>H</b>ello <em>World</em>"
     *
     *     HTML::clean('<a href="#">abc</a><em>de<b>f</em>', array('HTML.Allowed'=>'em,b'));
     *     //替换成 "abc<em>de<b>f</b></em>"
     *
     * [!!]
     * 可配置项参考 [Configuration Documentation](http://htmlpurifier.org/live/configdoc/plain.html)
     *
     * @param $strHtml HTML文本片段
     * @param array  可配置项
     * @return Purified
     */
    public static function clean($strHtml, array $confArr = array()){
        require_once  __DIR__.'/library/HTMLPurifier.auto.php';
        $config = HTMLPurifier_Config::createDefault();
        foreach($confArr as $key => $value){
            $config->set($key, $value);
        }
        $purifier = new HTMLPurifier($config);
        return $purifier->purify($strHtml);
    }
    /**
     * 获得HTML文本片段的字符串长度（忽略标签）
     *
     * @param $strHtml // HTML文本片段
     * @param string $charset
     * @return int 文本长度
     */
    public static function strlen($strHtml,$charset ='utf-8')
    {
        $strText = strip_tags($strHtml);
        return mb_strlen($strText, $charset);
    }

    /**
     * 获得字符串文本在某个HTML文本片段中出现的位置
     *
     * @param string // HTML文本片段
     * @param string // 要查找的字符串
     * @param int    // 查找起始位置
     * @param string $charset
     * @return int 返回字符串第一次出现的位置，若没有找到，返回`false`
     */
    public static function strpos($strHtml, $search, $offset = 0,$charset= 'utf-8')
    {
        $strText = strip_tags($strHtml);
        return mb_strpos($strText, $search, $offset, $charset);
    }

    /**
     * 从右往左查找字符串在某个HTML文本片段中出现的位置
     *
     * @param string HTML文本片段
     * @param string 要查找的字符串
     * @param int     查找起始位置
     * @param string $charset
     * @return int 返回字符串第一次出现的位置，若没有找到，返回`false`
     */
    public static function strrpos($strHtml, $search, $offset = 0,$charset= 'utf-8')
    {
        $strText = strip_tags($strHtml);
        return mb_strrpos($strText, $search, $offset, $charset);
    }

    /**
     * 按字长截取utf-8的html文本
     *
     * @param string    HTML文本片段
     * @param int        截取的起始位置，如果是负数，从结束符前开始计算
     * @param int        截取的长度，如果是-1，直接截取到末尾
     * @param string $charset
     * @return string    截取后的字符串（会自动补全html标签）
     */
    public static function substr($strHtml, $offset, $length = -1,$charset= 'utf-8')
    {
        $htmlTag = '/(<[^>]+>)/';
        $strText = strip_tags($strHtml);
        $strLen = mb_strlen($strText, $charset);
        if ($offset < 0) $offset = max(0, $strLen + $offset); //如果是负数，从结束符前开始计算
        if ($length < 0) $length = $strLen - $offset; //如果是负数，直接取到字符传末尾
        $texts = preg_split($htmlTag, $strHtml, -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
        //按html标记分割为字串
        $cursor = -$offset; //当前游标
        $result = array();
        foreach ($texts as $text) {
            if (substr($text, 0, 1) == '<') {
                array_push($result, $text); //如果是标记，直接push
                continue;
            }
            $subLen = mb_strlen($text, $charset); //字串的长度
            $s_pos = $cursor + $subLen;
            if ($s_pos < 0) { //说明还未开始
                $cursor = $s_pos;
                continue;
            }
            if ($s_pos >= $length) { //说明截取的字串恰好在这个子串中，取 -$cursor , $length
                array_push($result, mb_substr($text, -$cursor, $length, $charset));
                break;
            }
            if ($s_pos < $length) { //说明截取的字串包含了这个字串的一部分，取 -$cursor, -1
                array_push($result, mb_substr($text, -$cursor, $s_pos, $charset));
                $cursor = 0;
                $length -= $s_pos;
            }
        }

        $htmlResult = join('', $result);
        //自动补全缺失的标签，并过滤非法标签，防止XSS漏洞
        $htmlResult = self::clean($htmlResult);

        return $htmlResult;
    }

    /**
     * 从开始截取一定长度的富文本内容
     * @param $strHtml 要截取的HTML文本片段
     * @param $length  截取的长度
     * @param bool $ellipsis  是否显示省略号`...`
     * @param string $charset
     * @return string
     */
    public static function truncate($strHtml, $length, $ellipsis = TRUE,$charset= 'utf-8')
    {
        $strLen = self::strlen($strHtml);
        $more = $ellipsis ? '...' : '';
        if ($strLen > $length) {
            return self::substr($strHtml, 0, $length, $charset) . $more;
        } else {
            return $strHtml;
        }
    }
}

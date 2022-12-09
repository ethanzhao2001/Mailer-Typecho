<?php

namespace TypechoPlugin\Mailer;

use Typecho\Plugin\PluginInterface;
use Typecho\Widget\Helper\Form;
use Typecho\Widget\Helper\Form\Element\Text;
use Typecho\Widget\Helper\Form\Element\Radio;
use Typecho\Widget;
use Widget\Options;
use Utils\Helper;

require dirname(__FILE__) . '/PHPMailer/PHPMailer.php';
require dirname(__FILE__) . '/PHPMailer/SMTP.php';
require dirname(__FILE__) . '/PHPMailer/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}
/**
 *一个极简的邮件推送插件
 *
 *@package Mailer
 *@author 呆小萌
 *@version 1.1.1
 *@link https://www.zhaoyingtian.com/archives/mailer.html
 */

class Plugin implements PluginInterface
{
    /**
     *激活插件方法, 如果激活失败, 直接抛出异常
     */
    public static function activate()
    {
        //绑定事件
        \Typecho\Plugin::factory('Widget_Feedback')->finishComment = __CLASS__ . '::Comment';
        \Typecho\Plugin::factory('Widget_Comments_Edit')->finishComment = __CLASS__ . '::Comment';
        \Typecho\Plugin::factory('Widget_Comments_Edit')->mark = __CLASS__ . '::Approved';
        //异步
        \Typecho\Plugin::factory('Widget_Service')->SendMailComment = __CLASS__ . '::SendMailComment';
        \Typecho\Plugin::factory('Widget_Service')->SendMailApproved = __CLASS__ . '::SendMailApproved';
        \Typecho\Plugin::factory('Widget_Service')->CheckMail = __CLASS__ . '::CheckMail';
    }
    /**
     *禁用插件方法, 如果禁用失败, 直接抛出异常
     */
    public static function deactivate()
    {
    }
    /**
     *获取插件配置面板
     *
     *@param Form $form 配置面板
     */
    public static function config(Form $form)
    {
        //获取站长邮箱
        $user = Widget::widget('Widget_User');
        $mail = $user->mail;
        $adminMail = new Text('adminMail', NULL, $mail, _t('站长邮箱'));
        $form->addInput($adminMail);
        //SMTP服务器地址
        $smtpHost = new Text('smtpHost', NULL, 'smtp.qq.com', _t('SMTP服务器地址'));
        $form->addInput($smtpHost);
        //SMTP服务器端口
        $smtpPort = new Text('smtpPort', NULL, '465', _t('SMTP服务器端口'));
        $form->addInput($smtpPort);
        //SMTP安全模式
        $smtpSecure = new Radio('smtpSecure', array('' => _t('无加密'), 'ssl' => _t('SSL'), 'tls' => _t('TLS')), 'ssl', _t('SMTP加密模式'));
        $form->addInput($smtpSecure);
        //SMTP邮箱账号
        $smtpUser = new Text('smtpUser', NULL, NULL, _t('SMTP邮箱账号'));
        $form->addInput($smtpUser);
        //SMTP邮箱密码
        $smtpPass = new Text('smtpPass', NULL, NULL, _t('SMTP邮箱密码'));
        $form->addInput($smtpPass);
        //日志记录
        $log = new Radio('log', array('1' => _t('普通模式'), '2' => _t('测试模式')), '1', _t('日志记录'));
        $form->addInput($log);
        //异步发送
        $async = new Radio('async', array('1' => _t('开启'), '0' => _t('关闭')), '1', _t('异步发送'));
        $form->addInput($async);
    }
    /**
     *个人用户的配置面板
     *
     *@param Form $form
     */
    public static function personalConfig(Form $form)
    {
    }
    /**
     * 检查配置信息
     */
    public static function configCheck()
    {
        $options = Options::alloc();
        $Mailer = $options->plugin('Mailer');
        //测试日志
        if ($Mailer->log == 2) {
            $time = date('Y-m-d H:i:s', time());
            $fileName = dirname(__FILE__) . '/test.log';
            $test = $time . " configCheck\n";
            file_put_contents($fileName, $test, FILE_APPEND);
        }
        //异步发送
        if ($Mailer->async == 1) {
            Helper::requestService('CheckMail', $Mailer->adminMail);
        } else {
            self::CheckMail($Mailer->adminMail);
        }
    }
    public static function CheckMail($adminMail)
    {
        $options = Options::alloc();
        $Mailer = $options->plugin('Mailer');
        //测试日志
        if ($Mailer->log == 2) {
            $time = date('Y-m-d H:i:s', time());
            $fileName = dirname(__FILE__) . '/test.log';
            $test = $time . " CheckMail\n";
            file_put_contents($fileName, $test, FILE_APPEND);
        }
        self::smtp('Mailer 测试邮件', '如果能看到该邮件，那么你的插件配置应该是正确的。', $adminMail, $options->title);
    }
    /**
     *评论事件
     */
    public static function Comment($comment)
    {
        $options = Options::alloc();
        $Mailer = $options->plugin('Mailer');
        //测试日志
        if ($Mailer->log == 2) {
            $time = date('Y-m-d H:i:s', time());
            $fileName = dirname(__FILE__) . '/test.log';
            $test = $time . " Comment\n";
            file_put_contents($fileName, $test, FILE_APPEND);
        }
        $commentJson = self::commentJson($comment);
        //异步发送
        if ($Mailer->async == 1) {
            Helper::requestService('SendMailComment', $commentJson);
        } else {
            self::SendMailComment($commentJson);
        }
    }
    /**
     *审核事件
     */
    public static function Approved($comment, $edit, $status)
    {
        $options = Options::alloc();
        $Mailer = $options->plugin('Mailer');
        //测试日志
        if ($Mailer->log == 2) {
            $time = date('Y-m-d H:i:s', time());
            $fileName = dirname(__FILE__) . '/test.log';
            $test = $time . " Approved\n";
            file_put_contents($fileName, $test, FILE_APPEND);
        }
        if ($status == 'approved') {
            $commentJson = self::commentJson($edit);
            //异步发送
            if ($Mailer->async == 1) {
                Helper::requestService('SendMailApproved', $commentJson);
            } else {
                self::SendMailApproved($commentJson);
            }
        }
    }
    /**
     *commentJson
     */
    public static function commentJson($comment)
    {
        $options = Options::alloc();
        $Mailer = $options->plugin('Mailer');
        //测试日志
        if ($Mailer->log == 2) {
            $time = date('Y-m-d H:i:s', time());
            $fileName = dirname(__FILE__) . '/test.log';
            $test = $time . " commentJson\n";
            file_put_contents($fileName, $test, FILE_APPEND);
        }
        $commentOjb = (object)NULL;
        $commentOjb->coid = $comment->coid;
        $commentOjb->cid = $comment->cid;
        $commentOjb->author = $comment->author;
        $commentOjb->mail = $comment->mail;
        $commentOjb->url = $comment->url;
        $commentOjb->ip = $comment->ip;
        $commentOjb->authorId = $comment->authorId;
        $commentOjb->ownerId = $comment->ownerId;
        $commentOjb->agent = $comment->agent;
        $commentOjb->text = $comment->text;
        $commentOjb->type = $comment->type;
        $commentOjb->status = $comment->status;
        $commentOjb->parent = $comment->parent;
        $commentOjb->title = $comment->title;
        $commentOjb->permalink = $comment->permalink;
        $commentOjb->created = date('Y-m-d H:i:s', $comment->created);
        $commentJson = json_encode($commentOjb);
        return $commentJson;
    }
    //评论事件发信
    public static function SendMailComment($commentJson)
    {
        $options = Options::alloc();
        $Mailer = $options->plugin('Mailer');
        //测试日志
        if ($Mailer->log == 2) {
            $time = date('Y-m-d H:i:s', time());
            $fileName = dirname(__FILE__) . '/test.log';
            $test = $time . " SendMailComment\n";
            file_put_contents($fileName, $test, FILE_APPEND);
        }
        $comment = json_decode($commentJson);
        $userMail = $comment->mail; //评论者
        $adminMail = $Mailer->adminMail; //站长
        //获取父评论
        if ($comment->parent) {
            $parent = Helper::widgetById('comments', $comment->parent);
            $comment->parentMail = $parent->mail; //父评论者
            $comment->parentName = $parent->author;
            $comment->parentText = $parent->text;
        }
        //评论者与父评论者不同，且父评论不为空（关闭审核发送回复）
        if ($userMail != $comment->parentMail && $comment->parentMail != NULL) {
            if ($comment->status == 'approved' || $comment->parentMail == $adminMail) {
                self::sendReply($comment);
            }
        }
        //评论者或者父评论者是站长
        if ($userMail == $adminMail || $comment->parentMail == $adminMail) {
        } else {
            self::sendNotice($comment);
        }
    }
    //审核通过事件发信
    public static function SendMailApproved($commentJson)
    {
        $options = Options::alloc();
        $Mailer = $options->plugin('Mailer');
        //测试日志
        if ($Mailer->log == 2) {
            $time = date('Y-m-d H:i:s', time());
            $fileName = dirname(__FILE__) . '/test.log';
            $test = $time . " SendMailApproved\n";
            file_put_contents($fileName, $test, FILE_APPEND);
        }
        $comment = json_decode($commentJson);
        $userMail = $comment->mail; //评论者
        $adminMail = $Mailer->adminMail; //站长
        //获取父评论
        if ($comment->parent) {
            $parent = Helper::widgetById('comments', $comment->parent);
            $comment->parentMail = $parent->mail; //父评论者
            $comment->parentName = $parent->author;
            $comment->parentText = $parent->text;
        }
        //评论者不是站长
        if ($userMail != $adminMail && $userMail != NULL) {
            self::sendApproved($comment);
        }
        //评论者与父评论者不同，且父评论不为空(开启审核发送回复)
        if ($userMail != $comment->parentMail && $comment->parentMail != NULL) {
            if ($comment->parentMail != $adminMail) {
                self::sendReply($comment);
            }
        }
    }
    public static function sendReply($comment)
    {
        $options = Options::alloc();
        $Mailer = $options->plugin('Mailer');
        //测试日志
        if ($Mailer->log == 2) {
            $time = date('Y-m-d H:i:s', time());
            $fileName = dirname(__FILE__) . '/test.log';
            $test = $time . " sendReply\n";
            file_put_contents($fileName, $test, FILE_APPEND);
        }
        $mail = self::Mail('reply', $comment);
        self::smtp('你在《' . $comment->title . '》的评论有新的回复', $mail, $comment->parentMail, $comment->parentName);
    }
    public static function sendNotice($comment)
    {
        $options = Options::alloc();
        $Mailer = $options->plugin('Mailer');
        //测试日志 
        if ($Mailer->log == 2) {
            $time = date('Y-m-d H:i:s', time());
            $fileName = dirname(__FILE__) . '/test.log';
            $test = $time . " sendNotice\n";
            file_put_contents($fileName, $test, FILE_APPEND);
        }
        $mail = self::Mail('notice', $comment);
        self::smtp('《' . $comment->title . '》有新的评论', $mail, $Mailer->adminMail, $options->title);
    }
    public static function sendApproved($comment)
    {
        $options = Options::alloc();
        $Mailer = $options->plugin('Mailer');
        //测试日志
        if ($Mailer->log == 2) {
            $time = date('Y-m-d H:i:s', time());
            $fileName = dirname(__FILE__) . '/test.log';
            $test = $time . " sendApproved\n";
            file_put_contents($fileName, $test, FILE_APPEND);
        }
        $mail = self::Mail('approved', $comment);
        self::smtp('你在《' . $comment->title . '》的评论已通过审核', $mail, $comment->mail, $comment->author);
    }

    //邮件模板
    public static function Mail($theme, $comment)
    {
        $options = Options::alloc();
        $Mailer = $options->plugin('Mailer');
        //测试日志
        if ($Mailer->log == 2) {
            $time = date('Y-m-d H:i:s', time());
            $fileName = dirname(__FILE__) . '/test.log';
            $test = $time . " Mail\n";
            file_put_contents($fileName, $test, FILE_APPEND);
        }
        $ThemeFile = file_get_contents(dirname(__FILE__) . '/Theme/' . $theme . '.html');
        $search = array(
            '{time}', //评论发出时间
            '{author}', //昵称
            '{mail}', //邮箱
            '{url}', //网址
            '{ip}', //IP
            '{agent}', //UA
            '{text}', //内容
            '{parentName}', //父级评论昵称
            '{parentText}', //父级评论内容
            '{parentMail}', //父级评论邮箱
            '{title}', //文章标题
            '{permalink}', //评论链接
            '{siteTitle}', //网站标题
            '{siteUrl}', //网站地址
        );
        $replace = array(
            $comment->created,
            $comment->author,
            $comment->mail,
            $comment->url,
            $comment->ip,
            $comment->agent,
            $comment->text,
            $comment->parentName,
            $comment->parentText,
            $comment->parentMail,
            $comment->title,
            $comment->permalink,
            $options->title,
            $options->siteUrl,
        );
        return str_replace($search, $replace, $ThemeFile);
    }
    /**
     *SMTP邮件发送
     */
    public static function smtp($title, $html, $address, $name)
    {
        $options = Options::alloc();
        $Mailer = $options->plugin('Mailer');

        //测试日志
        if ($Mailer->log == 2) {
            $time = date('Y-m-d H:i:s', time());
            $fileName = dirname(__FILE__) . '/test.log';
            $test = $time . " smtp\n";
            file_put_contents($fileName, $test, FILE_APPEND);
        }
        //获取配置选项
        $mail = new PHPMailer(true);
        try {
            //SMTP服务器配置
            $mail->CharSet = 'UTF-8';
            $mail->isSMTP();
            $mail->Host = $Mailer->smtpHost;
            $mail->SMTPAuth = true;
            $mail->Username = $Mailer->smtpUser;
            $mail->Password = $Mailer->smtpPass;
            $mail->SMTPSecure = $Mailer->smtpSecure;
            $mail->Port = $Mailer->smtpPort;
            $mail->setFrom($Mailer->smtpUser, $options->title);
            $mail->addAddress($address, $name);
            $mail->isHTML(true);
            $mail->Subject = $title;
            $mail->Body = $html;
            $mail->send();
        } catch (Exception $e) {
            $time = date('Y-m-d H:i:s', time());
            $fileName = dirname(__FILE__) . '/error.log';
            $error = $time . "\n" .
                $mail->ErrorInfo . "\n" .
                'SMTP服务器：' . $Mailer->smtpHost . "\n" .
                'SMTP用户名：' . $Mailer->smtpUser . "\n" .
                'SMTP密码：' . $Mailer->smtpPass . "\n" .
                'SMTP端口：' . $Mailer->smtpPort . "\n" .
                'SMTP加密：' . $Mailer->smtpSecure . "\n" .
                '发件人：(' . $options->title . ')' . $Mailer->smtpUser . "\n" .
                '收件人：(' . $name . ')' . $address . "\n" .
                '标题：' . $mail->Subject . "\n" .
                '内容：' . $mail->Body . "\n" . "\n";
            file_put_contents($fileName, $error, FILE_APPEND);
        }
    }
}

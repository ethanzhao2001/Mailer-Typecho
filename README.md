用于 Typecho 博客程序具的评论邮件通知插件，仅支持 1.2.0

# 特性

高效：使用异步发信，不影响评论速度

简洁：后台简洁、代码简洁、核心代码不足300行

方便：保存插件配置自动发送测试邮件，自动记录发信错误日志可随时检查，正常发信无日志

拥有完善的发信逻辑，如下表所示：

| 评论者 | 上级评论者 | 新评论通知站长 | 上级被评论通知 | 评论者审核通过 |
| ------ | ---------- | -------------- | -------------- | -------------- |
| 站长   | -          | ×              | ×              | ×              |
| 站长   | 站长       | ×              | ×              | ×              |
| user1  | -          | √              | ×              | √              |
| user1  | user1      | √              | ×              | √              |
| 站长   | user1      | ×              | √              | ×              |
| user1  | 站长       | ×              | √              | √              |
| user1  | user2      | √              | √              | √               |

博客地址：https://www.zhaoyingtian.com/archives/mailer.html

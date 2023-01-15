用于 Typecho 博客程序具的评论邮件通知插件，仅支持 1.2.0 及以上版本

# 特性

高效：使用异步发信，不影响评论速度

简洁：后台简洁、代码简洁、核心代码不足300行

方便：保存插件配置自动发送测试邮件，自动记录发信错误日志可随时检查，正常发信无日志

好看：内置三个邮件模板，也支持自定义邮件模板


拥有完善的发信逻辑，如下表所示：

看不懂也没关系，总之体验很不错就对了！！！

| 评论者 | 上级评论者 | 新评论通知站长 | 回复上级评论 | 评论者审核通过 |
| ------ | ---------- | -------------- | -------------- | -------------- |
| 站长   | -          | ×              | ×              | ×              |
| 站长   | 站长       | ×              | ×              | ×              |
| user1  | -          | √              | ×              | √              |
| user1  | user1      | √              | ×              | √              |
| 站长   | user1      | ×              | √              | ×              |
| user1  | 站长       | ×              | √              | √              |
| user1  | user2      | √              | √              | √               |

# 模板

| 变量           | 含义         |
| -------------- | ------------ |
| {time}         | 评论时间     |
| {author}       | 昵称         |
| {avatar}       | 头像         |
| {text}         | 内容         |
| {mail}         | 邮箱         |
| {url}          | 网址         |
| {ip}           | IP           |
| {agent}        | UA           |
| {parentTime}   | 父级评论时间 |
| {parentName}   | 父级评论昵称 |
| {parentAvatar} | 父级评论头像 |
| {parentText}   | 父级评论内容 |
| {parentMail}   | 父级评论邮箱 |
| {title}        | 文章标题     |
| {permalink}    | 评论链接     |
| {siteTitle}    | 网站标题     |
| {siteUrl}      | 网站地址     |


# 说明

`宝塔/CDN防火墙`可能会拦截Typecho异步请求，也有可能是其他原因

解决方法（任选其一）：

1. 手动关闭异步模式
2. 将本机IP设置白名单
3. 防火墙关闭From-data协议

# 更新

1.1.0

增加 测试日志记录模式

增加 手动关闭异步以解决某些情况无法发信的问题

1.1.1

修复 站长重复收到信息的BUG

2.0

增加 评论者头像的变量

增加 多模板切换

博客地址：https://www.zhaoyingtian.com/archives/mailer.html

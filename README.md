# 分离ecshop的图片资源到七牛云存储上

## 一、数据库修改

在`ecs_shop_config`表中添加两条记录

![image](http://7xsj9j.com2.z0.glb.clouddn.com/tb_shop_config.png "数据库修改")

text类型表示文本，select类型表示单选或下拉框

## 二、语言文件修改

在languages/zh_cn/admin/shop_config.php 文件中添加几句代码：

```php 
$_LANG['cfg_name']['use_qiniu'] = '是否使用七牛云存储'; //新增
$_LANG['cfg_name']['qiniu_site_url'] = '七牛域名'; //新增

$_LANG['cfg_range']['use_qiniu'][0] = '不使用'; #新增
$_LANG['cfg_range']['use_qiniu'][1] = '使用'; #新增
```

修改之后，打开后台的商店设置->显示设置可以看到：

![image](http://7xsj9j.com2.z0.glb.clouddn.com/shop_config.png "语言文件修改")

此时就可以使用$GLOBALS['_CFG']['use_qiniu'] 和 $GLOBALS['_CFG']['qiniu_site_url'] 了。

## 三、接入七牛文件


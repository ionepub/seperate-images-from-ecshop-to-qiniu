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

PHP的文档在这里：[七牛官方PHP文档](http://developer.qiniu.com/code/v7/sdk/php.html)

这里使用的是上传文件功能。

需要使用到的文件有：

 · Qiniu文件夹（src文件夹里）

将Qiniu文件夹拷贝到admin/includes/下，并在admin/includes/下新建一个PHP文件 `qiniu_upload.php`，这个文件的内容有两块，一个是文档里说明的auto_loader的内容，另一个就是上传文件的内容。

需要在`qiniu_upload.php`中声明你的 Access Key 和 Secret Key，并指明你的bucket（要上传的空间名），这些内容都可以从七牛控制台获取到。

```php
  // 需要填写你的 Access Key 和 Secret Key
	$accessKey = '';
	$secretKey = '';

	// 构建鉴权对象
	$auth = new Auth($accessKey, $secretKey);

	// 要上传的空间
	$bucket = '';
```

## 在后台功能中使用

在需要上传图片的地方添加以下代码：

```php
/* 上传到七牛 */
if($_CFG['use_qiniu'] == 1 && $_CFG['qiniu_site_url'] != ""){
    $qiniu_file_path = $file_url;
    include_once './includes/qiniu_upload.php';
}
```

其中$qiniu_file_path即为要传入的文件的地址，如果要上传多个，则采用数组形式：（示例使用的是admin/goods.php文件部分代码）

```php
/* 插入商品 更新商品 */
elseif ($_REQUEST['act'] == 'insert' || $_REQUEST['act'] == 'update')
{
    /* 上传到七牛 */
    if($_CFG['use_qiniu'] == 1 && $_CFG['qiniu_site_url'] != ""){
        $qiniu_file_path = array(); // 数组上传
    }
    /* 
    code...
    */
    /* 重新格式化图片名称 */
    $original_img = reformat_image_name('goods', $goods_id, $original_img, 'source');
    $goods_img = reformat_image_name('goods', $goods_id, $goods_img, 'goods');
    $goods_thumb = reformat_image_name('goods_thumb', $goods_id, $goods_thumb, 'thumb');
    if ($goods_img !== false)
    {
        $db->query("UPDATE " . $ecs->table('goods') . " SET goods_img = '$goods_img' WHERE goods_id='$goods_id'");

        /* 上传到七牛 */
        if($_CFG['use_qiniu'] == 1 && $_CFG['qiniu_site_url'] != ""){
            $qiniu_file_path[] = $goods_img;
        }
    }
    if ($original_img !== false)
    {
        $db->query("UPDATE " . $ecs->table('goods') . " SET original_img = '$original_img' WHERE goods_id='$goods_id'");

        /* 上传到七牛 */
        if($_CFG['use_qiniu'] == 1 && $_CFG['qiniu_site_url'] != ""){
            $qiniu_file_path[] = $original_img;
        }
    }
    /* 
    code...
    */
    /* 上传到七牛 */
    if($_CFG['use_qiniu'] == 1 && $_CFG['qiniu_site_url'] != ""){
        $qiniu_file_path = array_unique($qiniu_file_path); // 去重
        include_once './includes/qiniu_upload.php';
    }
}
```

## 商品相册图片上传

如果需要将商品相册图片上传到七牛，需要修改一个函数。打开`/admin/includes/lib_goods.php`文件，找到function handle_gallery_image()，修改为以下代码即可。（有修改的代码都有标注上传到七牛，尽量不要整个函数一起复制）

```php
/**
 * 保存某商品的相册图片
 * @param   int     $goods_id
 * @param   array   $image_files
 * @param   array   $image_descs
 * @return  void
 */
function handle_gallery_image($goods_id, $image_files, $image_descs, $image_urls)
{
    /* 上传到七牛 */
    if($GLOBALS['_CFG']['use_qiniu'] == 1 && $GLOBALS['_CFG']['qiniu_site_url'] != ""){
        $qiniu_file_path = array(); // 数组上传
    }

    /* 是否处理缩略图 */
    $proc_thumb = (isset($GLOBALS['shop_id']) && $GLOBALS['shop_id'] > 0)? false : true;
    foreach ($image_descs AS $key => $img_desc)
    {
        /* 是否成功上传 */
        $flag = false;
        if (isset($image_files['error']))
        {
            if ($image_files['error'][$key] == 0)
            {
                $flag = true;
            }
        }
        else
        {
            if ($image_files['tmp_name'][$key] != 'none')
            {
                $flag = true;
            }
        }

        if ($flag)
        {
            // 生成缩略图
            if ($proc_thumb)
            {
                $thumb_url = $GLOBALS['image']->make_thumb($image_files['tmp_name'][$key], $GLOBALS['_CFG']['thumb_width'],  $GLOBALS['_CFG']['thumb_height']);
                $thumb_url = is_string($thumb_url) ? $thumb_url : '';
            }

            $upload = array(
                'name' => $image_files['name'][$key],
                'type' => $image_files['type'][$key],
                'tmp_name' => $image_files['tmp_name'][$key],
                'size' => $image_files['size'][$key],
            );
            if (isset($image_files['error']))
            {
                $upload['error'] = $image_files['error'][$key];
            }
            $img_original = $GLOBALS['image']->upload_image($upload);
            if ($img_original === false)
            {
                sys_msg($GLOBALS['image']->error_msg(), 1, array(), false);
            }
            $img_url = $img_original;

            if (!$proc_thumb)
            {
                $thumb_url = $img_original;
            }
            // 如果服务器支持GD 则添加水印
            if ($proc_thumb && gd_version() > 0)
            {
                $pos        = strpos(basename($img_original), '.');
                $newname    = dirname($img_original) . '/' . $GLOBALS['image']->random_filename() . substr(basename($img_original), $pos);
                copy('../' . $img_original, '../' . $newname);
                $img_url    = $newname;

                $GLOBALS['image']->add_watermark('../'.$img_url,'',$GLOBALS['_CFG']['watermark'], $GLOBALS['_CFG']['watermark_place'], $GLOBALS['_CFG']['watermark_alpha']);
            }

            /* 重新格式化图片名称 */
            $img_original = reformat_image_name('gallery', $goods_id, $img_original, 'source');
            $img_url = reformat_image_name('gallery', $goods_id, $img_url, 'goods');
            $thumb_url = reformat_image_name('gallery_thumb', $goods_id, $thumb_url, 'thumb');
            $sql = "INSERT INTO " . $GLOBALS['ecs']->table('goods_gallery') . " (goods_id, img_url, img_desc, thumb_url, img_original) " .
                    "VALUES ('$goods_id', '$img_url', '$img_desc', '$thumb_url', '$img_original')";
            $GLOBALS['db']->query($sql);
            /* 不保留商品原图的时候删除原图 */
            if ($proc_thumb && !$GLOBALS['_CFG']['retain_original_img'] && !empty($img_original))
            {
                $GLOBALS['db']->query("UPDATE " . $GLOBALS['ecs']->table('goods_gallery') . " SET img_original='' WHERE `goods_id`='{$goods_id}'");
                @unlink('../' . $img_original);
            }

            /* 上传到七牛 */
            if($GLOBALS['_CFG']['use_qiniu'] == 1 && $GLOBALS['_CFG']['qiniu_site_url'] != ""){
                array_push($qiniu_file_path, $img_url, $thumb_url, $img_original);
            }
        }
        elseif (!empty($image_urls[$key]) && ($image_urls[$key] != $GLOBALS['_LANG']['img_file']) && ($image_urls[$key] != 'http://') && copy(trim($image_urls[$key]), ROOT_PATH . 'temp/' . basename($image_urls[$key])))
        {
            $image_url = trim($image_urls[$key]);

            //定义原图路径
            $down_img = ROOT_PATH . 'temp/' . basename($image_url);

            // 生成缩略图
            if ($proc_thumb)
            {
                $thumb_url = $GLOBALS['image']->make_thumb($down_img, $GLOBALS['_CFG']['thumb_width'],  $GLOBALS['_CFG']['thumb_height']);
                $thumb_url = is_string($thumb_url) ? $thumb_url : '';
                $thumb_url = reformat_image_name('gallery_thumb', $goods_id, $thumb_url, 'thumb');
            }

            if (!$proc_thumb)
            {
                $thumb_url = htmlspecialchars($image_url);
            }

            /* 重新格式化图片名称 */
            $img_url = $img_original = htmlspecialchars($image_url);
            $sql = "INSERT INTO " . $GLOBALS['ecs']->table('goods_gallery') . " (goods_id, img_url, img_desc, thumb_url, img_original) " .
                    "VALUES ('$goods_id', '$img_url', '$img_desc', '$thumb_url', '$img_original')";
            $GLOBALS['db']->query($sql);

            @unlink($down_img);

            /* 上传到七牛 */
            if($GLOBALS['_CFG']['use_qiniu'] == 1 && $GLOBALS['_CFG']['qiniu_site_url'] != ""){
                array_push($qiniu_file_path, $img_url, $thumb_url, $img_original);
            }
        }
    }

    /* 上传到七牛 */
    if($GLOBALS['_CFG']['use_qiniu'] == 1 && $GLOBALS['_CFG']['qiniu_site_url'] != ""){
        $qiniu_file_path = array_unique($qiniu_file_path); // 去重
        include_once './includes/qiniu_upload.php';
    }
}
```

## 后台编辑器上传图片

商品详情里的图片是比较多的，所以需要将商品详情里的图片也上传到七牛。

修改的文件是 `/includes/kindeditor/php/upload_json.php`文件，找到第126行左右，添加代码：

```php
  /*code...*/
  //新文件名
	$new_file_name = date("YmdHis") . '_' . rand(10000, 99999) . '.' . $file_ext;
	//移动文件
	$file_path = $save_path . $new_file_name;
	if (move_uploaded_file($tmp_name, $file_path) === false) {
		alert("上传文件失败。");
	}
	@chmod($file_path, 0644);
	$file_url = $save_url . $new_file_name;

  // 这两行是需要添加的内容，要注意的是如果后台不开启图片转存，直接将这两行注释掉就行
	/* 上传到七牛 */
	$qiniu_file_path = str_replace($php_url . '../../../images/upload/', '../../images/upload/', $file_url);
  include_once '../../../admin/includes/qiniu_upload.php';

	header('Content-type: text/html; charset=UTF-8');
	$json = new Services_JSON();
	echo $json->encode(array('error' => 0, 'url' => $file_url));
	exit;
	/*code...*/
```

## 前台模板修改

首先在公共文件`includes/lib_main.php`里找到function assign_template加上这句：

```php
$smarty->assign('qiniu_site_url', $GLOBALS['_CFG']['qiniu_site_url']);
```

这样能保证在所有的模板页面都能使用qiniu_site_url这个变量。如果不希望这样，可以在相应的文件里添加，比如goods.php, index.php等。

接着找到相应的模板文件，在img标签的src属性前加上七牛的域名地址即可：

```html
<img src="{$qiniu_site_url}{$goods.thumb}" alt="">
```

TIP: 有一个要注意的问题就是，在后台商店设置中填写七牛的域名地址时，最后加上一个/，因为在ecshop的模板中，基本上所有的图片路径前都没有加上这个/，如果忘记加上这个，渲染出来的地址可能就是类似这样的：

`www.qiniusiteurl.comimages/goods_thumb/1234.jpg`


<?php

// Need_Remodified 检查生成所文件夹与文件的读写权限
define('UPLOAD_OBJ_DIR', ($_REQUEST['domainset'] ? $_REQUEST['domainset'] . '/' : ''));
// define('UPLOAD_OBJ_DOMAIN', './');
define('UPLOAD_OBJ_DOMAIN', 'http://' . $_SERVER['HTTP_HOST'] . '/' . ($_REQUEST['domainset'] ? $_REQUEST['domainset'] . '/' : ''));
// define('UPLOAD_OBJ_DOMAIN', 'http://img' . mt_rand(1, 4) . '.yzz.cn/' . ($_REQUEST['domainset'] ? $_REQUEST['domainset'] . '/' : ''));
define('API_SECRET', 'test');

function takeVerify($data)
{
    $beforeSign = $data['api_sign'];
    unset($data['api_sign']);

    $data['api_secret'] = API_SECRET;

    $afterSign = getSign($data);

    if ($beforeSign != $afterSign) {
        die("verify not right");
    }
}

function getSign($data)
{
    $sign = '';
    if (is_array($data)
        && !empty($data['api_key'])
        && !empty($data['api_secret'])) {
        $apiSecret = $data['api_secret'];
        unset($data['api_secret']);
        ksort($data);
        $sign .= implode('', $data);
        $sign .= $apiSecret;
        $sign = md5($sign);
    }
    return $sign;
}

// 图片文件安全性分析，检验图片文件的类型是否符合标准，并生成针对gd库的图片文件句柄与图片信息。
function getImgHandlerAndInfo($filePath, $boolgetHandler = true)
{
	$rs = array('path'=> $filePath);
	if ($filePath
		&& ($rs['info'] = (@getimagesize($filePath)))
		&& (! empty($rs['info'][0]))
		&& (! empty($rs['info'][1]))
	){
		if ($boolgetHandler) {
			if ($rs['info'][2] === IMAGETYPE_JPEG) {
				$rs['handler'] = imagecreatefromjpeg($filePath);
			} elseif ($rs['info'][2] === IMAGETYPE_PNG) {
				$rs['handler'] = imagecreatefrompng($filePath);
			} elseif ($rs['info'][2] === IMAGETYPE_GIF) {
				$rs['handler'] = imagecreatefromgif($filePath);
			} else {
				$rs = 'ERR_IMG_TYPE';
			}
		} elseif (! in_array($rs['info'][2], array(IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF))) {
			$rs = 'ERR_IMG_TYPE';
		}
	} else {
		$rs = 'ERR_IMG_FORMAT';
	}
	return $rs;
}

// 相应的上传目录的确定（如果不存在，则生成新的）
function crtCmsUploadDir($domainSet)
{
	if ($domainSet
		&& ctype_alnum($domainSet)						// 安全性检验，确保不会建非法目录
		&& (! in_array($domainSet, array('phpapp')))	// 禁止生成与上传的目录
	){
		// 图片目录命名规则：前缀/[域名]/img[yy]/[mm]/[dd]/
		$dirPath = UPLOAD_OBJ_DIR.date('Y').'/'.date('m').'/'.date('d');		// for window security, NOT add .'/' to the end

		if (is_dir($dirPath)
			|| (@mkdir($dirPath, 0755, true))	// 最小化权限设置
		){
			$rs = $dirPath;
		} else {
			$rs = array('ERR_MK_DIR'=> $dirPath);
		}

		// you can use the clearstatcache() function to clear the information that PHP caches about a file. 
		// You should also note that PHP doesn't cache information about non-existent files. 
		// So, if you call file_exists() on a file that doesn't exist, it will return FALSE until you create the file. 
		// If you create the file, it will return TRUE even if you then delete the file. However unlink() clears the cache automatically.

	} else {
		$rs = array('ERR_DIRNAME'=> $domainSet);
	}
	return $rs;
}


// 文件预处理：水印
function waterPntImg($srcImgObj, $objImg = false)
{
	$imgx_watermarktype = 1;		// 水印类型，1表示png水印
	$imgx_watermarkstatus = 9;		// 水印位置，9表示右下脚，0表示随机水印位置
	$imgx_watermarktrans = 0;		// 水印透明度，0表示不透明
	$watermark = './mark.png';		// 水印图片地址：./mark.png

	if (! $objImg) {
		$objImg = $srcImgObj['path'];
	}

	// 加水印的图片的尺寸
	if ($srcImgObj['info'][0] >= 120		// 要加水印的图片最小宽度
		&& $srcImgObj['info'][1] >= 120		// 要加水印的图片最小高度
	){
		// 获取水印pointer
		$watermark = getImgHandlerAndInfo($watermark, true);
		if (is_array($watermark)){
			// 水印图片尺寸小于目标图片
			if ($srcImgObj['info'][0] - $watermark['info'][0] > 10
				&& $srcImgObj['info'][1] - $watermark['info'][1] > 10
			){
				// 0表示随机水印位置
				if($imgx_watermarkstatus == 0)
				{
					$imgx_watermarkstatus = rand(1, 9);
				}
				
				// 水印位置值对应出坐标
				switch($imgx_watermarkstatus)
				{
					case 1:	
						$x = +5;
						$y = +5;
						break;
					case 2:
						$x = ($srcImgObj['info'][0] - $watermark['info'][0]) / 2;
						$y = +5;
						break;
					case 3:
						$x = $srcImgObj['info'][0] - $watermark['info'][0] - 5;
						$y = +5;
						break;
					case 4:
						$x = +5;
						$y = ($srcImgObj['info'][1] - $watermark['info'][1]) / 2;
						break;
					case 5:
						$x = ($srcImgObj['info'][0] - $watermark['info'][0]) / 2;
						$y = ($srcImgObj['info'][1] - $watermark['info'][1]) / 2;
						break;
					case 6:
						$x = $srcImgObj['info'][0] - $watermark['info'][0] - 5;
						$y = ($srcImgObj['info'][1] - $watermark['info'][1]) / 2;
						break;
					case 7:
						$x = +5;
						$y = $srcImgObj['info'][1] - $watermark['info'][1] - 5;
						break;
					case 8:
						$x = ($srcImgObj['info'][0] - $watermark['info'][0]) / 2;
						$y = $srcImgObj['info'][1] - $watermark['info'][1] - 5;
						break;
					case 9:
						$x = $srcImgObj['info'][0] - $watermark['info'][0] - 5;
						$y = $srcImgObj['info'][1] - $watermark['info'][1] - 5;
						break;
				}

				if (($dst_photo = @imagecreatetruecolor($srcImgObj['info'][0], $srcImgObj['info'][1]))
					&& imagecopy($dst_photo, $srcImgObj['handler'], 0, 0, 0, 0, $srcImgObj['info'][0], $srcImgObj['info'][1])
					&& (  $imgx_watermarktype == 1
						  && imagecopy($dst_photo, $watermark['handler'], $x, $y, 0, 0, $watermark['info'][0], $watermark['info'][1])
						) || (
						  imagealphablending($watermark['handler'], true)
						  && imagecopymerge($dst_photo, $watermark['handler'], $x, $y, 0, 0, $watermark['info'][0], $watermark['info'][1], $imgx_watermarktrans)
						)
				){		
					switch ($srcImgObj['info'][2]) {
						case IMAGETYPE_JPEG:
							imagejpeg($dst_photo, $objImg, 85);
							break;
						case IMAGETYPE_PNG:
							imagepng($dst_photo, $objImg);
							break;
						case IMAGETYPE_GIF:
							imagegif($dst_photo, $objImg);
							break;
					}		
					$rs = $objImg;
				} else {
					$rs = array('ERR_WATERPNT'=> $dst_photo);
				}
				imagedestroy($dst_photo);
			} else {
				$rs = $objImg;
			}
			imagedestroy($watermark['handler']);
		} else {
			$rs = array('ERR_WATERMARK'=> $watermark);
		}
	} else {
		$rs = $objImg;
	}

	return $rs;
}

/**
 * 图片等比例缩放，优化版
 *
 * author Baob 2012-02-27
 */
function ImageResizeOpt($srcImgObj, $objWidth, $objHeight, $objImg = false)
{
	if ($objImg === true) {
		$file_extension = '.'.pathinfo($srcImgObj['path'], PATHINFO_EXTENSION);
		$objImg = str_replace($file_extension, ('_lit.'.$file_extension), $srcImgObj['path']);
	}

	if ($objWidth > $srcImgObj['info'][0]
		&& $objHeight > $srcImgObj['info'][1]
	){
		if ($objImg) {
			if (copy($srcImgObj['path'], $objImg)) {
				$rs = $objImg;
			} else {
				$rs = array('ERR_COPY'=> $objImg);
			}
		} else {
			$rs = $srcImgObj;
		}
	} elseif (in_array($srcImgObj['info'][2], array(IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF))) {
    
        $width = $srcImgObj['info'][0];
        $height = $srcImgObj['info'][1];
        
        $cutW = $cutH = 0;
        $newW = $objWidth;
        $newH = $objHeight;
        
        if ($width <= $height) {
            $newW = ($objHeight / $height) * $width;
        } else {
            $newH = ($objWidth / $width) * $height;
        }
        
        if ($newW <= $objWidth) {
            $r = $objWidth / $newW;
            $newW = $newW * $r;
            $newH = $newH * $r;
            $cutH = ($newH - $objHeight) / 2;
        }
        if ($newH <= $objHeight) {
            $r = $objHeight/ $newH;
            $newH = $newH * $r;
            $newW = $newW * $r;
            $cutW = ($newW - $objWidth) / 2;
        }
        
		if ((function_exists('imagecreatetruecolor')
			  && (@$ni = imagecreatetruecolor($objWidth, $objHeight))
			  && imagecopyresampled($ni, $srcImgObj['handler'], 0, 0, $cutW, $cutH, $newW, $newH, $srcImgObj['info'][0], $srcImgObj['info'][1])
			) || (
			 ($ni = imagecreate($objWidth, $objHeight))
			  && imagecopyresized($ni, $srcImgObj['handler'], 0, 0, $cutW, $cutH, $newW, $newH, $srcImgObj['info'][0], $srcImgObj['info'][1])
			)
		){
			if (! $objImg) {
				$objImg = $srcImgObj['path'];
			}
			switch ($srcImgObj['info'][2]) {
				case IMAGETYPE_JPEG:
					imagejpeg($ni, $objImg, 85);
					break;
				case IMAGETYPE_PNG:
					imagepng($ni, $objImg);
					break;
				case IMAGETYPE_GIF:
					imagegif($ni, $objImg);
					break;
			}
			$rs = $objImg;
		} else {
			$rs = array('ERR_SHRINK'=> $objImg);
		}
		imagedestroy($ni);
	} else {
		$rs = array('ERR_IMGTYPE'=> $srcImgObj);
	}

	return $rs;
}
// 文件预处理：缩略原图（来源支持gif、jpg、png）
function thumbImage($srcImgObj, $objWidth, $objHeight, $objImg = false)
{
	if ($objImg === true) {
		$file_extension = '.'.pathinfo($srcImgObj['path'], PATHINFO_EXTENSION);
		$objImg = str_replace($file_extension, ('_lit.'.$file_extension), $srcImgObj['path']);
	}

	if ($objWidth >= $srcImgObj['info'][0]
		&& $objHeight >= $srcImgObj['info'][1]
	){
		if ($objImg) {
			if (copy($srcImgObj['path'], $objImg)) {
				$rs = $objImg;
			} else {
				$rs = array('ERR_COPY'=> $objImg);
			}
		} else {
			$rs = $srcImgObj;
		}
	} elseif (in_array($srcImgObj['info'][2], array(IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF))) {
		$toWH = $objWidth / $objHeight;
		$srcWH = $srcImgObj['info'][0] / $srcImgObj['info'][1];
		if ($toWH <= $srcWH) {
			$ftoW = $objWidth;
			$ftoH = $ftoW * ($srcImgObj['info'][1] / $srcImgObj['info'][0]);
		} else {
			$ftoH = $objHeight;
			$ftoW = $ftoH * ($srcImgObj['info'][0] / $srcImgObj['info'][1]);
		}

		if ((function_exists('imagecreatetruecolor')
			  && (@$ni = imagecreatetruecolor($ftoW, $ftoH))
			  && imagecopyresampled($ni, $srcImgObj['handler'], 0, 0, 0, 0, $ftoW, $ftoH, $srcImgObj['info'][0], $srcImgObj['info'][1])
			) || (
			 ($ni = imagecreate($ftoW, $ftoH))
			  && imagecopyresized($ni, $srcImgObj['handler'], 0, 0, 0, 0, $ftoW, $ftoH, $srcImgObj['info'][0], $srcImgObj['info'][1])
			)
		){
			if (! $objImg) {
				$objImg = $srcImgObj['path'];
			}
			switch ($srcImgObj['info'][2]) {
				case IMAGETYPE_JPEG:
					imagejpeg($ni, $objImg, 85);
					break;
				case IMAGETYPE_PNG:
					imagepng($ni, $objImg);
					break;
				case IMAGETYPE_GIF:
					imagegif($ni, $objImg);
					break;
			}
			$rs = $objImg;
		} else {
			$rs = array('ERR_SHRINK'=> $objImg);
		}
		imagedestroy($ni);
	} else {
		$rs = array('ERR_IMGTYPE'=> $srcImgObj);
	}

	return $rs;
}

// 图片缩略处理集函数
function thumbImgAsRequest($srcFile, $filePrefix, $file_extension, $imgObj)
{
	$rs = '';
	// 生成原图缩略（来源支持gif、jpg、png）
	if (isset($_REQUEST['dd2'])
		&& $_REQUEST['dd2']
		&& isset($_REQUEST['w2'])
		&& ($_REQUEST['w2'] = ((int) $_REQUEST['w2']))
		&& isset($_REQUEST['h2'])
		&& ($_REQUEST['h2'] = ((int) $_REQUEST['h2']))
		&& (isset($imgObj['handler']) 
			|| ($imgObj = getImgHandlerAndInfo($srcFile, true))
			)
	){
		$rs = thumbImage($imgObj, $_REQUEST['w2'], $_REQUEST['h2'], $filePrefix.'_lit'.$file_extension);
	} elseif (isset($_REQUEST['dd'])
		&& $_REQUEST['dd'] == 'yes'
		&& isset($_REQUEST['w'])
		&& ($_REQUEST['w'] = ((int) $_REQUEST['w']))
		&& isset($_REQUEST['h'])
		&& ($_REQUEST['h'] = ((int) $_REQUEST['h']))
		&& (isset($imgObj['handler']) 
			|| ($imgObj = getImgHandlerAndInfo($srcFile, true))
			)
	){
		$rs = thumbImage($imgObj, $_REQUEST['w'], $_REQUEST['h'], $filePrefix.'_lit'.$file_extension);
	} elseif (isset($_REQUEST['dd3'])
		&& $_REQUEST['dd3']
		&& isset($_REQUEST['w3'])
		&& ($_REQUEST['w3'] = ((int) $_REQUEST['w3']))
		&& isset($_REQUEST['h3'])
		&& ($_REQUEST['h3'] = ((int) $_REQUEST['h3']))
		&& (isset($imgObj['handler']) 
			|| ($imgObj = getImgHandlerAndInfo($srcFile, true))
			)
	){
		$rs = ImageResizeOpt($imgObj, $_REQUEST['w3'], $_REQUEST['h3'], $filePrefix.$file_extension);
	}
	/*
	// 生成针对图片集的缩略图（100*75）
	if ((! is_array($rs))
		&& (! empty($_REQUEST['auto_gen_pic_set_small_pic']))
		&& (isset($imgObj['handler']) 
			|| ($imgObj = getImgHandlerAndInfo($srcFile, true))
			)
	){
		$rs = ImageResizeOpt($imgObj, 100, 75, $filePrefix.'_picset_lit'.$file_extension);
	}
	*/
	// 删除文件资源句柄
	if (isset($imgObj['handler'])) {
		imagedestroy($imgObj['handler']);
	}
	
	return $rs;
}

// 单图片上传处理函数
function single_upload_img($imgPtr, $authMsg, $uploadDir)
{
    global $img_handle;
	// 上传文件判断检测，图片大小是否超限由 php.ini 控制
	if (isset($imgPtr['tmp_name'])
//		&& is_uploaded_file($imgPtr['tmp_name'])
		&& $imgPtr['error'] == 0
	){
		// 图片文件安全性分析，检验图片文件的类型是否符合标准，并生成针对gd库的图片文件句柄与图片信息。
		$imgObj = getImgHandlerAndInfo($imgPtr['tmp_name'], (isset($_REQUEST['water']) && $_REQUEST['water'] == 'yes'));

		if (is_array($imgObj)) {
			// 上传图片（文件保存）
			$file_extension = str_replace('.jpeg', '.jpg', image_type_to_extension($imgObj['info'][2]));

			// 尝试生成的次数少于20次，否则报错
			for ($tryTimes = 1; $tryTimes < 20; ++$tryTimes) {

				// 图片文件命名规则：[用户id]_[时分秒]_[文件名md5的随机数起5个字符的长度].[小写扩展名]
				$filePrefix = $uploadDir.'/'.date('His').'_'.substr(md5($imgPtr['name']), rand(0, 25), 5);
				$srcFile = $filePrefix.$file_extension;
				if (file_exists($srcFile)) {
					continue;
				} elseif (isset($_REQUEST['water'])
						  && $_REQUEST['water'] == 'yes'
				){
					// 生成水印（120*35）
					$uploadRs = waterPntImg($imgObj, $srcFile);
					if (is_array($uploadRs)) {
						$uploadRs = false;
						$tryTimes += 3;
						continue;
					} else {
						break;
					}
//				} elseif ($uploadRs = @move_uploaded_file($imgPtr['tmp_name'], $srcFile)) {
				} elseif ($uploadRs = copy($imgPtr['tmp_name'], $srcFile)) {
					break;
				}
			}
            $img_handle[basename($srcFile)] = array('width' =>$imgObj['info'][0], 'height' =>$imgObj['info'][1]);
			// 删除文件资源句柄
			if (isset($imgObj['handler'])) {
				imagedestroy($imgObj['handler']);
				unset($imgObj);
			}

			if (empty($uploadRs)) {
				$rs = array('ERR_IMG_MOV'=> $uploadRs);
			} else {
                $rs = $srcFile;
				// 文件处理：缩略原图（来源支持gif、jpg、png）
				$rs = thumbImgAsRequest($srcFile, $filePrefix, $file_extension, $imgObj);
				if (is_array($rs)) {
					unlink($srcFile);
				} else {
					$rs = $srcFile;
				}
			}
		} else {
			$rs = array('ERR_IMG_JUDGE'=> $imgObj);
		}
	} else {
		// Usually we'll only get an invalid upload if our PHP.INI upload sizes are smaller than the size of the file we allowed
		// to be uploaded.
		$rs = array('ERR_UPLOADFILE'=> $imgPtr['error']);
	}
	return $rs;
}

// 获取 链接数组 对应的图片文件：file_get_contents()
function getArrImg8ArrLnk($arrImg, $authMsg, $uploadDir)
{
    global $img_handle;
	$rs = array('ori'=> array(), 'obj'=> array());
	foreach ($arrImg as $imgURL) {
		if (strpos($imgURL, 'http://') === 0
			&& (in_array(substr($imgURL, -4), array('.jpg', '.gif', '.png'))
				|| substr($imgURL, -5) === '.jpeg'
				)
			&& ($imgObj = getImgHandlerAndInfo($imgURL, false))		// 检验图片的合法性
			&& is_array($imgObj)
			&& ($tmpImgCont = @file_get_contents($imgURL))
		){
			// 上传图片（文件保存）
			$file_extension = str_replace('.jpeg', '.jpg', image_type_to_extension($imgObj['info'][2]));
			$uploadRs = false;

			// 尝试生成的次数少于8次，否则报错
			for ($tryTimes = 1; $tryTimes < 8; ++$tryTimes) {
				// 图片文件命名规则：[用户id]_[时分秒]_[文件名md5的随机数起5个字符的长度].[小写扩展名]
				$filePrefix = $uploadDir.'/'.date('His').'_'.substr(md5($imgObj['path']), rand(0, 25), 5);
				$srcFile = $filePrefix.$file_extension;

				if (file_exists($srcFile)) {
					continue;
				} elseif ($uploadRs = @file_put_contents($srcFile, $tmpImgCont)) {
					break;
				}
			}
            $img_handle[basename($srcFile)] = array('width' =>$imgObj['info'][0], 'heigth' =>$imgObj['info'][1]);
			if ($uploadRs) {
				$rs['ori'][] = $imgURL;
				$rs['obj'][] = str_replace(UPLOAD_OBJ_DIR, (''.UPLOAD_OBJ_DOMAIN), $srcFile);
			}
		}
	}
	return $rs;
}

// 生成处理后的图片html
function crtHtmCod4Fck($rs)
{
    global $img_handle;
	for ($i = 1,$j = count($rs); $i <= $j; ++$i) {
		if ((! empty($rs[$i]))
			&& is_string($rs[$i])
		){
			if (isset($_REQUEST['instruction'.$i])
				&& $_REQUEST['instruction'.$i] != ''
			){
				$picinstruction = '<br>'.$_REQUEST['instruction'.$i];
			}
                
			// $rs[$i] = str_replace(UPLOAD_OBJ_DIR, (''.UPLOAD_OBJ_DOMAIN), $rs[$i]);
            $str = '';
            if (isset($img_handle[basename($rs[$i])])) {
                $str = 'width="' . $img_handle[basename($rs[$i])]['width'] . '" height="' . $img_handle[basename($rs[$i])]['height'] . '"';
            }
			$rs[$i] = '<img border="0" ' . $str . ' src="'.(empty($_REQUEST['dd'])? $rs[$i]: str_replace('.jpg', '_lit.jpg', $rs[$i])).'"'.(empty($_REQUEST['instruction'.$i])? '': (' alt="'.$_REQUEST['instruction'.$i].'"')).' />'.$picinstruction;
	
			if (empty($_POST['trigAddPager'])) {
				$rs[$i] = $rs[$i].'<br />'."\r\n";
			} else {
				$rs[$i] = '<p>'.$rs[$i].'</p>';
                if ($i > 1) {
                    $rs[$i] = '#p#' . $_REQUEST['instruction'.$i] . '#e#' . $rs[$i];
                }
			}
		} else {
			unset($rs[$i]);
		}
	}
	$rs = implode((empty($_POST['trigAddPager']) ? '': "\r\n"), $rs);
	return $rs;
}

// 过程化执行函数
function upload_exe()
{
	// 权限判断(白名单控制)
    if ($_POST['Filedata']['tmp_name'] && $rs = file_put_contents($_POST['Filedata']['tmp_name'], $_POST['content'])) {
        $domainPath = (empty($_REQUEST['domainset'])? 'pub': str_replace('/', '', $_REQUEST['domainset']));

        // 相应的上传目录的确定（如果不存在，则生成新的）
        $uploadDir = crtCmsUploadDir($domainPath);
        if (is_string($uploadDir)) {
            if (isset($_POST['Filedata'])) {
                $rs = array( 1 => single_upload_img($_POST['Filedata'], $authMsg, $uploadDir));
                $rs = crtHtmCod4Fck($rs);
            } else if (isset($_REQUEST['totalform'])
                       && ($_REQUEST['totalform'] = ((int) $_REQUEST['totalform']))
            ){
                $rs = array();
                for ($i = 1; $i <= $_REQUEST['totalform']; ++$i) {
                    if (isset($_FILES['imgfile'.$i])) {
                        $rs[$i] = single_upload_img($_REQUEST['imgfile'.$i], $authMsg, $uploadDir);
                    }
                }
                $rs = crtHtmCod4Fck($rs);
            } else if ((! empty($_REQUEST['remote_img']))
                       && is_string($_REQUEST['remote_img'])
                       && ($_REQUEST['remote_img'] = @unserialize(get_magic_quotes_gpc()? stripslashes($_REQUEST['remote_img']): $_REQUEST['remote_img']))
                       && is_array($_REQUEST['remote_img'])
            ){
                $rs = getArrImg8ArrLnk($_REQUEST['remote_img'], $authMsg, $uploadDir);
                if (empty($rs['ori'])) {
                    $rs = array('UPLOAD_IMGSTR_EMPTY'=> $rs, 'remote_img'=> $_REQUEST['remote_img']);
                } else {
                    $rs = serialize($rs);
                }
            }
        } else {
            $rs = $uploadDir;
        }
    } else {
        $rs = array('UPLOAD_ERR');
    }
	return $rs;
}

// error_reporting(-1);
takeVerify($_POST);
$_POST['Filedata'] = json_decode($_POST['Filedata'], TRUE);
$img_handle = array();
$rs = upload_exe();
// 操作记录与数据应答
if (isset($_POST['Filedata'])) {
	if (is_array($rs)) {
		// In this demo we trigger the uploadError event in SWFUpload by returning a status code other than 200 (which is the default returned by PHP)
		header('HTTP/1.1 500 File Upload Error');
		echo 'ERR_COD';
		// var_dump($rs);
	} else {
		$rs = str_replace(UPLOAD_OBJ_DIR, (''.UPLOAD_OBJ_DOMAIN), $rs);
		echo $rs;
	}
} elseif (isset($_REQUEST['totalform'])
		  && ($_REQUEST['totalform'] = ((int) $_REQUEST['totalform']))
		  && (! empty($_SERVER['HTTP_REFERER']))
){
	if (is_array($rs)) {
		$rs = 'UPLOAD_ERR';
	}
	$refer_url = parse_url($_SERVER['HTTP_REFERER']);
	$refer_url = $refer_url['scheme'].'://'.$refer_url['host'].(isset($refer_url['port'])? (':'.$refer_url['port']): '').$refer_url['path'].'?bVar='.urlencode("\r\n".$rs."\r\n");

	header('Location: '.$refer_url);
} elseif (! empty($_REQUEST['remote_img'])) {
	if (is_array($rs)) {
		$rs = 'UPLOAD_ERR';
		// $rs = serialize($rs);
	}
	echo $rs;
}
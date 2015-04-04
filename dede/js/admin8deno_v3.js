
function readCookie(name)
{
	var nameEQ = name + "=";
	var ca = document.cookie.split(';');
	for(var i=0;i < ca.length;i++) {
		var c = ca[i];
		while (c.charAt(0)==' ') c = c.substring(1,c.length);
		if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
	}
	return null;
}

function delPic(iconUpload)
{
	document.getElementById('img_'+iconUpload).src = 'about:blank';
	document.getElementById('picname_'+iconUpload).value = '';
	document.getElementById('size_'+iconUpload).innerHTML = '';
	arrSwfu[iconUpload].cancelUpload();
}

function pic_fileQueueError(file, errorCode, message) {
	try {
		if (errorCode === SWFUpload.QUEUE_ERROR.QUEUE_LIMIT_EXCEEDED) {
			alert("You have attempted to queue too many files.\n" + (message === 0 ? "You have reached the upload limit." : "You may select " + (message > 1 ? "up to " + message + " files." : "one file.")));
			return;
		}
		
		switch (errorCode) {
		case SWFUpload.QUEUE_ERROR.FILE_EXCEEDS_SIZE_LIMIT:
			//progress.setStatus("File is too big.");
			this.debug("Error Code: File too big, File name: " + file.name + ", File size: " + file.size + ", Message: " + message);
			break;
		case SWFUpload.QUEUE_ERROR.ZERO_BYTE_FILE:
			//progress.setStatus("Cannot upload Zero Byte files.");
			this.debug("Error Code: Zero byte file, File name: " + file.name + ", File size: " + file.size + ", Message: " + message);
			break;
		case SWFUpload.QUEUE_ERROR.INVALID_FILETYPE:
			//progress.setStatus("Invalid File Type.");
			this.debug("Error Code: Invalid File Type, File name: " + file.name + ", File size: " + file.size + ", Message: " + message);
			break;
		default:
			//if (file !== null) {
			//	progress.setStatus("Unhandled Error");
			//}
			this.debug("Error Code: " + errorCode + ", File name: " + file.name + ", File size: " + file.size + ", Message: " + message);
			break;
		}
	} catch (ex) {
        this.debug(ex);
    }
}

function pic_fileDialogComplete(numFilesSelected, numFilesQueued) {
	try {
		/* I want auto start the upload and I can do that here */
		this.startUpload();
	} catch (ex)  {
        this.debug(ex);
	}
}

function pic_uploadSuccess(file, serverData) {
	try {
		serverData = serverData.split('+')
		serverData = serverData[serverData.length - 1];
		try {
			var match = serverData.match('src="([^"]+)"');
			if (!match || 'undefined' === typeof match[1]) {
				var src = serverData;
			} else {
				var src = match[1];
			}
		} catch(e) {
			var src = serverData;
		}
		document.getElementById('picname_'+this.customSettings.iconNum).value = src;
		var oriImg = document.getElementById('img_'+this.customSettings.iconNum);
		oriImg.src = src;

		var sizeObj = 'size_' + this.customSettings.iconNum;
		var imagetag = new Image();
		imagetag.src = src;
		imagetag.onload = function() {
			document.getElementById(sizeObj).innerHTML = '尺寸('+imagetag.width+'x'+imagetag.height+')';
			var tmpImgD = imgZoom(imagetag.width, imagetag.height, 120, 80);
			oriImg.style.width = parseInt(tmpImgD.width)+'px';
			oriImg.style.height = parseInt(tmpImgD.height)+'px';
		};
	} catch (ex) {
		this.debug(ex);
	}
}

function pic_uploadError(file, errorCode, message) {
	try {
		switch (errorCode) {
		case SWFUpload.UPLOAD_ERROR.HTTP_ERROR:
			//progress.setStatus("Upload Error: " + message);
			this.debug("Error Code: HTTP Error, File name: " + file.name + ", Message: " + message);
			break;
		case SWFUpload.UPLOAD_ERROR.UPLOAD_FAILED:
			//progress.setStatus("Upload Failed.");
			this.debug("Error Code: Upload Failed, File name: " + file.name + ", File size: " + file.size + ", Message: " + message);
			break;
		case SWFUpload.UPLOAD_ERROR.IO_ERROR:
			//progress.setStatus("Server (IO) Error");
			this.debug("Error Code: IO Error, File name: " + file.name + ", Message: " + message);
			break;
		case SWFUpload.UPLOAD_ERROR.SECURITY_ERROR:
			//progress.setStatus("Security Error");
			this.debug("Error Code: Security Error, File name: " + file.name + ", Message: " + message);
			break;
		case SWFUpload.UPLOAD_ERROR.UPLOAD_LIMIT_EXCEEDED:
			//progress.setStatus("Upload limit exceeded.");
			this.debug("Error Code: Upload Limit Exceeded, File name: " + file.name + ", File size: " + file.size + ", Message: " + message);
			break;
		case SWFUpload.UPLOAD_ERROR.FILE_VALIDATION_FAILED:
			//progress.setStatus("Failed Validation.  Upload skipped.");
			this.debug("Error Code: File Validation Failed, File name: " + file.name + ", File size: " + file.size + ", Message: " + message);
			break;
		case SWFUpload.UPLOAD_ERROR.FILE_CANCELLED:
			// If there aren't any files left (they were all cancelled) disable the cancel button
			//if (this.getStats().files_queued === 0) {
			//	document.getElementById(this.customSettings.cancelButtonId).disabled = true;
			//}
			//progress.setStatus("Cancelled");
			//progress.setCancelled();
			break;
		case SWFUpload.UPLOAD_ERROR.UPLOAD_STOPPED:
			//progress.setStatus("Stopped");
			break;
		default:
			//progress.setStatus("Unhandled Error: " + errorCode);
			this.debug("Error Code: " + errorCode + ", File name: " + file.name + ", File size: " + file.size + ", Message: " + message);
			break;
		}
	} catch (ex) {
        this.debug(ex);
    }
}

function displayImg8src(iconNum)
{
	var imgSrc = document.getElementById('picname_'+iconNum).value;

	if (imgSrc != '') {
		var sizeObj = 'size_' + iconNum;
		var imagetag = new Image();
		imagetag.onload = function() {
			document.getElementById(sizeObj).innerHTML = '上传图片('+imagetag.width+'*'+imagetag.height+')';
			var tmpImgD = imgZoom(imagetag.width, imagetag.height, 120, 80);
			document.getElementById('img_'+iconNum).style.width = parseInt(tmpImgD.width)+'px';
			document.getElementById('img_'+iconNum).style.height = parseInt(tmpImgD.height)+'px';
		};
		imagetag.src = document.getElementById('img_'+iconNum).src;
	}
}

function imgZoom(actWidth, actHeight, maxWidth, maxHeight)
{
	var ImgD = {width:0, height:0};
	if (actWidth>0 && actHeight>0) {
		if (actWidth/actHeight >= maxWidth/maxHeight){   
		   if (actWidth>maxWidth) {     
			   ImgD.width=maxWidth;
			   ImgD.height=(actHeight*maxWidth)/actWidth;   
		   } else {
			   ImgD.width=actWidth;   
			   ImgD.height=actHeight;
		   }
		} else {   
		   if (actHeight>maxHeight) {
			   ImgD.height=maxHeight;
			   ImgD.width=(actWidth*maxHeight)/actHeight;
		   } else {
			   ImgD.width=actWidth;
			   ImgD.height=actHeight;
		   }
		}
	}
	return ImgD;
}

var arrSwfu = {};


var settings = {
	flash_url : "../include/FCKeditor/editor/js/swfupload/swfupload.swf",
	// flash_url: "http://61.146.192.218:99/swfupload.swf",
	// upload_url: ("../include/FCKeditor/editor/dialog/uploadfile4swfupload.php?domainset="+swfupload_domainSet),
	// upload_url: ("http://61.146.192.218:99/upload8jdj_effort.php?domainset="+swfupload_domainSet),
	upload_url: ("../plus/redirect.php"),
	post_params: {"PHPSESSID" : readCookie('PHPSESSID'), "dd3" : 'true', 'w3' : 120, 'h3' : 80, "domainset" : swfupload_domainSet},
	file_size_limit : "10 MB",
	file_types : "*.jpg;*.gif;*.jpeg;*.png",
	file_types_description : "Web Image Files",
	file_upload_limit : 100,
	file_queue_limit : 1,
	debug: false,
	prevent_swf_caching : false, 

	// Button settings
	button_image_url: "../include/FCKeditor/editor/images/TestImageNoText_65x29.png",
	button_width: "50",
	button_height: "20",
	button_text: '上传',
	button_text_left_padding: 10,
	button_text_top_padding: 0,
	
	// The event handler functions are defined in handlers.js
	//file_queued_handler : fileQueued,
	file_queue_error_handler : pic_fileQueueError,
	file_dialog_complete_handler : pic_fileDialogComplete,
	//upload_start_handler : uploadStart,
	//upload_progress_handler : uploadProgress,
	upload_error_handler : pic_uploadError,
	upload_success_handler : pic_uploadSuccess
	//upload_complete_handler : uploadComplete,
	//queue_complete_handler : queueComplete	// Queue plugin event
};
for (var i=0; i<=9; ++i) {
    if (i == 0) i = '';
    var tmpIconNum = "iconUpload"+i;
    var tmpIconNumswf = "swf_" + tmpIconNum;
    if (!document.getElementById(tmpIconNumswf)) continue;
    settings.button_placeholder_id = "swf_"+tmpIconNum;
    settings.custom_settings = {
		iconNum: tmpIconNum
	};	
	arrSwfu[tmpIconNum] = new SWFUpload(settings);
	displayImg8src(tmpIconNum);
}
$(".add_swf_liptic").bind("click", function () {
    var parentid = $(this).parent().attr("class");
    var index = parentid.replace('swf_liptic', '');
    
    if (index == '') {
        var html = '<h4>缩略图<br/>(120x80)</h4>';
    }
	
    html += '<span class="btndelfile" onClick="delPic(\'iconUpload' + index + '\');"></span><img id="img_iconUpload' + index + '" src="about:blank"/><span class="imgSize" id="size_iconUpload'+ index + '"></span><span id="swf_iconUpload' + index + '"></span><input type="hidden" name="picname' + index + '" id="picname_iconUpload' + index + '" value=""/><input type="hidden" name="ddisremote' + index + '" id="ddisremote' + index + '" value="1"/>';
    
    $("." + parentid).html(html);
    
    var tmpIconNum = "iconUpload"+index;
    var tmpIconNumswf = "swf_" + tmpIconNum;
    settings.button_placeholder_id = "swf_"+tmpIconNum;
    settings.custom_settings = {
		iconNum: tmpIconNum
	};	
	// console.log(settings);
	arrSwfu[tmpIconNum] = new SWFUpload(settings);
	// console(arrSwfu);
	displayImg8src(tmpIconNum);
    return false;
})

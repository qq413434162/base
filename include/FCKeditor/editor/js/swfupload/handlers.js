/* Demo Note:  This demo uses a FileProgress class that handles the UI for displaying the file name and percent complete.
The FileProgress class is not part of SWFUpload.
*/


/* **********************
   Event Handlers
   These are my custom event handlers to make my
   web application behave the way I went when SWFUpload
   completes different tasks.  These aren't part of the SWFUpload
   package.  They are part of my application.  Without these none
   of the actions SWFUpload makes will show up in my application.
   ********************** */
function fileQueued(file) {
	try {
		var progress = new FileProgress(file, this.customSettings.progressTarget);
		progress.setStatus("Pending...");
		progress.toggleCancel(true, this);

	} catch (ex) {
		this.debug(ex);
	}

}

function fileQueueError(file, errorCode, message) {
	try {
		if (errorCode === SWFUpload.QUEUE_ERROR.QUEUE_LIMIT_EXCEEDED) {
			alert("You have attempted to queue too many files.\n" + (message === 0 ? "You have reached the upload limit." : "You may select " + (message > 1 ? "up to " + message + " files." : "one file.")));
			return;
		}

		var progress = new FileProgress(file, this.customSettings.progressTarget);
		progress.setError();
		progress.toggleCancel(false);

		switch (errorCode) {
		case SWFUpload.QUEUE_ERROR.FILE_EXCEEDS_SIZE_LIMIT:
			progress.setStatus("File is too big.");
			this.debug("Error Code: File too big, File name: " + file.name + ", File size: " + file.size + ", Message: " + message);
			break;
		case SWFUpload.QUEUE_ERROR.ZERO_BYTE_FILE:
			progress.setStatus("Cannot upload Zero Byte files.");
			this.debug("Error Code: Zero byte file, File name: " + file.name + ", File size: " + file.size + ", Message: " + message);
			break;
		case SWFUpload.QUEUE_ERROR.INVALID_FILETYPE:
			progress.setStatus("Invalid File Type.");
			this.debug("Error Code: Invalid File Type, File name: " + file.name + ", File size: " + file.size + ", Message: " + message);
			break;
		default:
			if (file !== null) {
				progress.setStatus("Unhandled Error");
			}
			this.debug("Error Code: " + errorCode + ", File name: " + file.name + ", File size: " + file.size + ", Message: " + message);
			break;
		}
	} catch (ex) {
        this.debug(ex);
    }
}

function fileDialogComplete(numFilesSelected, numFilesQueued) {
	try {
		if (numFilesSelected > 0) {
			document.getElementById(this.customSettings.cancelButtonId).disabled = false;
		}
		
		/* I want auto start the upload and I can do that here */
		this.startUpload();
	} catch (ex)  {
        this.debug(ex);
	}
}

function uploadStart(file) {
	try {
		/* I don't want to do any file validation or anything,  I'll just update the UI and
		return true to indicate that the upload should start.
		It's important to update the UI here because in Linux no uploadProgress events are called. The best
		we can do is say we are uploading.
		 */
		
		var progress = new FileProgress(file, this.customSettings.progressTarget);
		progress.setStatus("Uploading...");
		progress.toggleCancel(true, this);
	}
	catch (ex) {}
	
	return true;
}

function uploadProgress(file, bytesLoaded, bytesTotal) {
	try {
		var percent = Math.ceil((bytesLoaded / bytesTotal) * 100);

		var progress = new FileProgress(file, this.customSettings.progressTarget);
		progress.setProgress(percent);
		progress.setStatus("Uploading...");
	} catch (ex) {
		this.debug(ex);
	}
}

function uploadSuccess(file, serverData) {
	try {
		var progress = new FileProgress(file, this.customSettings.progressTarget);
		progress.setComplete();
		progress.setStatus("Complete.");
		progress.toggleCancel(false);
		//addImage(serverData);
		fileUploaded8swfUpload[fileUploaded8swfUpload.length] = serverData;

	} catch (ex) {
		this.debug(ex);
	}
}

function uploadError(file, errorCode, message) {
	try {
		var progress = new FileProgress(file, this.customSettings.progressTarget);
		progress.setError();
		progress.toggleCancel(false);

		switch (errorCode) {
		case SWFUpload.UPLOAD_ERROR.HTTP_ERROR:
			progress.setStatus("Upload Error: " + message);
			this.debug("Error Code: HTTP Error, File name: " + file.name + ", Message: " + message);
			break;
		case SWFUpload.UPLOAD_ERROR.UPLOAD_FAILED:
			progress.setStatus("Upload Failed.");
			this.debug("Error Code: Upload Failed, File name: " + file.name + ", File size: " + file.size + ", Message: " + message);
			break;
		case SWFUpload.UPLOAD_ERROR.IO_ERROR:
			progress.setStatus("Server (IO) Error");
			this.debug("Error Code: IO Error, File name: " + file.name + ", Message: " + message);
			break;
		case SWFUpload.UPLOAD_ERROR.SECURITY_ERROR:
			progress.setStatus("Security Error");
			this.debug("Error Code: Security Error, File name: " + file.name + ", Message: " + message);
			break;
		case SWFUpload.UPLOAD_ERROR.UPLOAD_LIMIT_EXCEEDED:
			progress.setStatus("Upload limit exceeded.");
			this.debug("Error Code: Upload Limit Exceeded, File name: " + file.name + ", File size: " + file.size + ", Message: " + message);
			break;
		case SWFUpload.UPLOAD_ERROR.FILE_VALIDATION_FAILED:
			progress.setStatus("Failed Validation.  Upload skipped.");
			this.debug("Error Code: File Validation Failed, File name: " + file.name + ", File size: " + file.size + ", Message: " + message);
			break;
		case SWFUpload.UPLOAD_ERROR.FILE_CANCELLED:
			// If there aren't any files left (they were all cancelled) disable the cancel button
			if (this.getStats().files_queued === 0) {
				document.getElementById(this.customSettings.cancelButtonId).disabled = true;
			}
			progress.setStatus("Cancelled");
			progress.setCancelled();
			break;
		case SWFUpload.UPLOAD_ERROR.UPLOAD_STOPPED:
			progress.setStatus("Stopped");
			break;
		default:
			progress.setStatus("Unhandled Error: " + errorCode);
			this.debug("Error Code: " + errorCode + ", File name: " + file.name + ", File size: " + file.size + ", Message: " + message);
			break;
		}
	} catch (ex) {
        this.debug(ex);
    }
}

function uploadComplete(file) {
	if (this.getStats().files_queued === 0) {
		document.getElementById(this.customSettings.cancelButtonId).disabled = true;
	}
}

// This event comes from the Queue Plugin
function queueComplete(numFilesUploaded) {
	var status = document.getElementById("divStatus");
	status.innerHTML = '&nbsp;（'+ numFilesUploaded + "个文件上传成功）";

	var boolMid = document.getElementById('wordPosMid').checked;
    var boolTap = document.getElementById("trigAddPager2").checked;

	var imgHtml = [];
	var txt4alt = document.getElementById('txt4alt').value.split('\n');
	while (txt4alt && txt4alt.length && txt4alt[txt4alt.length-1] == '') {
		txt4alt.pop();
	}
	var txt4alt_split, alt, img_title, url, width, hegiht, txt4alt_split_last, img_title_last;
	var tuinfo = [];//start 图片站功能
    var html = '';
	for (var i=0, j = fileUploaded8swfUpload.length; i<j; ++i) {
		if (document.getElementById('auto_gen_empty_text').checked && txt4alt && txt4alt.length && (! txt4alt[i])) {
			txt4alt[i] = txt4alt[txt4alt.length-1];
		}
        //add by Baob 2012-02-28 Cause:图片说明和img元素中的alt单独起来
        //全都是空的情况下
        if ('undefined' == typeof txt4alt[i] || !txt4alt[i]) {
            txt4alt[i] = '';
        }
		txt4alt_split = txt4alt[i].split('|');
		alt = txt4alt_split[0].replace('\r', '');
		img_title = txt4alt_split[1] ? txt4alt_split[1] : txt4alt_split[0];
        
        url = fileUploaded8swfUpload[i].match(/src="([^"+]+)"/)[1];
		
        width = fileUploaded8swfUpload[i].match(/width="([^"+]+)"/)[1];
        height = fileUploaded8swfUpload[i].match(/height="([^"+]+)"/)[1];
		
		html = '';
		if (document.getElementById('dd2').checked) {
			width = parseInt(document.getElementById('w2').value);
			height = parseInt(document.getElementById('h2').value);
			
			if(width) {
				html += 'width="' + width + '"';
			}
			if(height) {
				html += ' height="' + height + '"';
			}
		}
		
        html = "<p><img " + html + " "+ (alt? ("alt='"+alt+"' "): '') +"src='"+ (document.getElementById('dd2').checked?(url.replace('.jpg', '_lit.jpg').replace('.jpeg', '_lit.jpeg').replace('.gif', '_lit.gif').replace('.png', '_lit.png')):url) +"'/></p>";
        
		if (true === boolTap && i == 0 && j == 1) {
			//html = alt + '<p>' + img_title +  '</p>#e#' + html;
			html = html + '<p>' + img_title +  '</p>';
		} else if (true === boolTap && i == 0) {
			//html = alt + '#e#' + html;
			//html = alt + '#e#' + html;
		}  else if (true === boolTap && (i > 0) && (i < j - 1)) {
			txt4alt_split_last = txt4alt[i-1].split('|');
			img_title_last = txt4alt_split_last[1] ? txt4alt_split_last[1] : txt4alt_split_last[0];
            html = '<p>' + img_title_last + '</p>#p#' + alt + '#e#' + html;
		} else if (true === boolTap && (i > 0) && i == j - 1) {
			txt4alt_split_last = txt4alt[i-1].split('|');
			img_title_last = txt4alt_split_last[1] ? txt4alt_split_last[1] : txt4alt_split_last[0];
            html = '<p>' + img_title_last + '</p>#p#' + alt + '#e#' + html + '<p>' + img_title +  '</p>';
		}
        imgHtml.push(html);
		
        /*
		var post_to_cms = []
		post_to_cms.push(fileUploaded8swfUpload[i]);
		post_to_cms.push(alt);
		post_to_cms.push(img_title? (boolMid?(''+img_title):(txt4alt[i])): '');
		post_to_cms.push(image_info[0]);//hash
		post_to_cms.push(image_info[1]);//width
		post_to_cms.push(image_info[2]);//height
		post_to_cms.push(image_info[3]);//type
        tuinfo.push(post_to_cms.join('++'))
        */
		//tuinfo.push(fileUploaded8swfUpload[i] + "++" + alt + "++" + (img_title? (boolMid?(''+img_title):(txt4alt[i])): ''));
	}
	imgHtml = imgHtml.join("");
	ImageOK2(imgHtml);
}

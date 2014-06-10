$(function()
{
	var $gallery = $('#gallery');
	var $empty = $('#empty_alert');
	var $uploader = $('#image_upload');
	
	$uploader.fileupload({
		dataType: 'json',
		acceptFileTypes: /(\.|\/)(gif|jpe?g|png)$/i,
        maxFileSize: 5000000,
        singleFileUploads: true,
        limitConcurrentUploads: 10
	});
	$uploader.on('fileuploadadd', function(e, data)
	{
		if (data.files && data.files[0])
		{
			$empty.hide();
			var $galleryItem = $($('#gallery_item_template').html());
			data.context = $galleryItem.appendTo($gallery);
				
			var $progressBar = $('<div class="progress floating"><div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%;"></div></div>');
			$galleryItem.find('a.thumbnail').append($progressBar);
		
			var $photo = $galleryItem.find('div.img-placeholder');
			$photo.css('opacity', '0.4');
	            
			data.submit();
		}
	});
	
	$uploader.on('fileuploaddone', function(e, data)
	{
		var $thumbCell = data.context.find('.thumb-cell');
		$thumbCell.find('div').remove();
		
		if (typeof data.result !== 'undefined')
		{
			if (typeof data.result.error === 'undefined' && typeof data.result.photo !== 'undefined')
			{
				$thumbCell.html('');
				var $img = $('<img src="' + data.result.photo.url + '" alt="photo" />');
				$img.appendTo($thumbCell);
			}
			else
			{
				$('#error_modal').find('#error_modal_content').html('<div class="alert alert-danger">' + (data.result.error || 'Upload failed.') + '</div>');
				$('#error_modal').find('.modal-dialog').removeClass('modal-lg');
				$('#error_modal').modal('show');
				data.context.remove();
				if ($gallery.children().length < 1)
				{
					$empty.show();
				}
			}
		}
	});
	
	$uploader.on('fileuploadfail', function(e, data)
	{
		$('#error_modal').find('#error_modal_content').html(data.jqXHR.responseText);
		$('#error_modal').find('.modal-dialog').addClass('modal-lg');
		$('#error_modal').modal('show');
	});
	
	$uploader.on('fileuploadalways', function(e, data)
	{
		data.context.find('.progress').remove();
	});
	
	$uploader.on('fileuploadprogress', function(e, data)
	{
		var progress = data.loaded / data.total * 100;
		data.context.find('.progress-bar').css('width', progress + '%');
	});
	
	/*
	$gallery.sortable({
		placeholder: "gallery-item-placeholder",
		items: 'div.gallery-item.photo.editable',
		cursor: 'move',
		containment: 'parent',
		handle: '.toolbar .drag-handle'
	});
	$gallery.disableSelection();
	*/

});

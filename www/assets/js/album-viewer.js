$(function()
{
	var count = 16;
	
	var $gallery = $('#gallery');
	var $loading = $('#gallery_loading');
	var $empty = $('#empty_alert');
	
	$gallery.on('click', 'div.gallery-item div.thumb-cell img', function() {
		// TODO Handle clicks on gallery images
		var $galleryItem = $(this).closest('.gallery-item');
		var photo = $galleryItem.data('photo');
		console.log(photo);
	});
	
	$gallery.on('click', 'div.gallery-item div.toolbar a.action-delete', function() {
		// TODO Handle clicks on delete buttons
		var $galleryItem = $(this).closest('.gallery-item');
		var photo = $galleryItem.data('photo');
		console.log(photo);
	});
	
	var loadMore = function() {
		
		if (!$gallery.data('noMore'))
		{
		
			$loading.show();
			
			var data = {
				'album_id': $gallery.data('albumId'),
				'start': $gallery.data('start'),
				'count': count
			};
			
			jQuery.ajax({
				type: "POST",
				url: $gallery.data('loadUrl'),
				data: data,
				dataType: 'json',
				success: function(data, textStatus, jqXHR) {
					if (data.error)
					{
						// TODO Better way to handle errors
						$('#error_modal').find('#error_modal_content').html('<div class="alert alert-danger">' + data.error + '</div>');
						$('#error_modal').find('.modal-dialog').removeClass('modal-lg');
						$('#error_modal').modal('show');
						return;
					}
					
					$gallery.data('start', $gallery.data('start') + count);
					
					if (data.photos && data.photos.length > 0)
					{
						$empty.hide();
						
						for (var index in data.photos)
						{
							var photo = data.photos[index];
							var $galleryItem = $($('#gallery_item_template').html()).appendTo($gallery);
							var $thumbCell = $galleryItem.find('.thumb-cell');
							var $img = $('<img src="' + photo.thumbUrl + '" alt="photo" />');
							
							$galleryItem.data('photo', photo);
							
							$thumbCell.find('div.img-placeholder').remove();
							$thumbCell.prepend($img);
						}
					}
					else
					{
						$gallery.data('noMore', '1');
					}
				},
				error: function( jqXHR, textStatus, errorThrown) {
					
					$('#error_modal').find('#error_modal_content').html(jqXHR.responseText);
					$('#error_modal').find('.modal-dialog').addClass('modal-lg');
					$('#error_modal').modal('show');
					
				},
				complete: function(jqXHR, textStatus) {
					$loading.hide();
				}
			});
		}
	};
	
	$(window).scroll(function()
	{
		if ($(window).scrollTop() + $(window).height() == $(document).height())
		{
			loadMore();
		}
	});
	
	loadMore();
	
});
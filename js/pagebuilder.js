var pb_moduleHtml, pb_inited = false, pb_renderUrl;

$(document).ready(function($)
{
	var file = $('#pb_src');
	pb_renderUrl = file.attr('data-renderUrl');
});

/**
 * Initialise PageBuilder
 */
function pb_init()
{
	// Only run this stuff once
	if (!pb_inited) {
		pb_inited = true;

		// Delete element when &times; is clicked
		$(document).on('click', '#pbAdmin .layout li .close', function()
		{
			var container = $(this).parent().parent().parent();
			if (confirm('Are you sure you want to remove this?')) {
				container.fadeOut(400, function()
				{
					$(this).remove();
					pb_update();
				});
			}
		});

		// Update HTML if stuff is typed
		$(document).on('change paste', '#pbAdmin textarea', function()
		{
			pb_update();
		});

		$(document).on('click', '#pbAdmin .imgControl', function()
		{
			var file_frame,
				content = $(this).parent().parent().parent().find('textarea');

			if (file_frame) {
				file_frame.open();
				return;
			}

			file_frame = wp.media.frames.file_frame = wp.media({
				title: 'Insert Image',
				button: {
					'text': 'Insert'
				},
				multiple: false
			});

			file_frame.on('select', function()
			{
				attachment = file_frame.state().get('selection').first().toJSON();
				content.val(content.val() + attachment.url);
			});

			file_frame.open();
		});
	}

	// Generate handles
	$('#pbAdmin .layout li').each(function()
	{
		if ($(this).find('#pbLayout').length == 0) {
			var cl = $(this).attr('private') == 'false' ? '' : ' handle-disabled';
			$(this).prepend('<span class="handle' + cl + '">' + $(this).attr('name') + '<span class="controls"><span class="imgControl icon-cog"></span><span class="close">&times;</span></span></span>');
		}
	});

	// Hide the content editor
	$('#postdivrich').hide();

	// Save a snapshot of the modules
	pb_moduleHtml = $('.layout-modules').html();

	// Run the system
	pb_update();
}

/**
 * Updates everything
 */
function pb_update()
{
	// Reinstantiate the sortable process
	$('#pbAdmin .layout').sortable({
		handle: '.handle:not(.handle-disabled)',
		start: function(e, ui)
		{
			ui.item.addClass('active');
		},
		stop: function(e, ui)
		{
			// Reset the modules bar
			if (ui.item.attr('class') == 'module active') {
				ui.item.removeClass('module');
				$('.layout-modules').html(pb_moduleHtml);
			}
			ui.item.removeClass('active');
			pb_update();
		},
		connectWith: '#pbAdmin .layout:not(.layout-locked)',
		placeholder: 'pbPlaceholder'
	}).disableSelection();

	// Update the preview
	var data = pb_getData('#pbLayout');

	$('#pbData').val(JSON.stringify(data));

	$.get(pb_renderUrl, {data: data}, function(response)
	{
		var container = $('#pbPreview');
		container.fadeOut(200, function()
		{
			container.html(response);
			container.fadeIn(200);
		})
	});

	// Resize textareas
	$('#pbAdmin textarea').each(function()
	{
		$(this).height($(this)[0].scrollHeight);
	});
}

/**
 * Parses data from the #pbLayout area
 *
 * @param string $selector
 * @return array
 */
function pb_getData(selector)
{
	var elements = $(selector).find('>.layout>li>.content');
	var data = [];
	elements.each(function()
	{
		var textareas = $(this).children('textarea'),
			ref = $(this).parent().attr('ref');

		if (textareas.length > 0) {
			textareas.each(function()
			{
				var content = $(this).val();
				data.push({
					'ref': ref,
					'content': content,
				});
			});
		} else {
			data.push({
				'ref': ref,
				'content' : pb_getData(this)
			});
		}
	});
	return data;
}
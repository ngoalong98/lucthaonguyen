(function($){
	'use strict';
	var max_fields      = 10; //maximum input boxes allowed
    var wrapper         = $(".repeatable"); //Fields wrapper
    var add_button      = $(".add"); //Add button class
   
    var x = repeater.start - 1; //initlal text box count

    // Media Uploader Assets
    var defaultImage = 'http://via.placeholder.com/100/E51B23/fff?text=WPFomo';

    $(add_button).click(function(e){ //on add input button click
        e.preventDefault();
        if(x < max_fields){ //max input box allowed
            x++; //text box increment
        	var titleCounter = x + 1;
            var string_html = '<div id="table-id-'+x+'">';
            	string_html += '<h2>Fomo '+titleCounter+'</h2>';
            	string_html += '<table class="form-table" style="max-width: 600px;">';
                string_html += '<tr valign="top">';
                string_html += '<th scope="row">Primary Text</th>';
                string_html += '<td><input type="text" class="widefat" name="wpfomo_primary_text['+x+']" value=""></td>';
                string_html += '</tr>';
                string_html += '<tr valign="top">';
                string_html += '<th scope="row">Link Text</th>';
                string_html += '<td><input type="text" class="widefat" name="wpfomo_link_text['+x+']" value=""></td>';
                string_html += '</tr>';
                string_html += '<tr valign="top">';
                string_html += '<th scope="row">Link Url</th>';
                string_html += '<td><input type="text" class="widefat" name="wpfomo_url['+x+']" value=""></td>';
                string_html += '</tr>';
                string_html += '<tr valign="top">';
                string_html += '<th scope="row">Upload Image</th>';
                string_html += '<td>';
                string_html += '<div class="upload">';
                string_html += '<img data-src="'+defaultImage+'" src="'+defaultImage+'" width="100px" height="100px" />';
                string_html += '<div>';
                string_html += '<input type="hidden" name="wpfomo_image_url['+x+']" id="wpfomo_image_url['+x+']" value="" />';
                string_html += '<button type="submit" class="upload_image_button button">Upload</button>';
                string_html += '<button type="submit" class="remove_image_button button">Delete</button>';
                string_html += '</div>';
                string_html += '</div>';
                string_html += '</td>';
                string_html += '</tr>';
                string_html += '<tr valign="top">';
                string_html += '<th scope="row">Secondary Text</th>';
                string_html += '<td><input type="text" class="widefat" name="wpfomo_secondary_text['+x+']" value=""></td>';
                string_html += '</tr>';
                string_html += '<tr valign="top">';
                string_html += '<td><input type="button" data-id="'+x+'" class="button wpfomo-delete-btn button-danger remove_field" value="Remove Item" /></td>';
                string_html += '</tr>';
                string_html += '<tr valign="top">';
                string_html += '<td colspan="3" ><hr></td>';
                string_html += '</tr>';
                string_html += '</table>';
                string_html += '</div>';
                
            $(wrapper).append(string_html); //add input box
            /**
			 * Media Uploader
			 */
			// The "Upload" button
			$('.upload_image_button').click(function() {
			    var send_attachment_bkp = wp.media.editor.send.attachment;
			    var button = $(this);
			    wp.media.editor.send.attachment = function(props, attachment) {
			        $(button).parent().prev().attr('src', attachment.url);
			        $(button).prev().val(attachment.id);
			        wp.media.editor.send.attachment = send_attachment_bkp;
			    }
			    wp.media.editor.open(button);
			    return false;
			});

			// The "Remove" button (remove the value from input type='hidden')
			$('.remove_image_button').click(function() {
			    var answer = confirm('Are you sure?');
			    if (answer == true) {
			        var src = $(this).parent().prev().attr('data-src');
			        $(this).parent().prev().attr('src', src);
			        $(this).prev().prev().val('');
			    }
			    return false;
			});
        }
    });
   
    $( wrapper ).on( 'click', '.remove_field', function() { 
        var removeId = $(this).data( 'id' );
        console.log( removeId );
        $( '#table-id-'+removeId ).remove();
        x--;
    });
})(jQuery);

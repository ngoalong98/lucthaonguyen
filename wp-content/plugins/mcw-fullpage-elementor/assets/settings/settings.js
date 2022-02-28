( function ( $ ) {
  $( document ).ready( function() {

    $( '.fp-extension-install' ).unbind( 'click.fpextensioninstall' ).bind( 'click.fpextensioninstall', function( e ) {
      // Get target button
      var $button = $( e.target );
      var $parent = $button.parent();
      var $input = $parent.find( 'input' );
      var $note = $parent.find( '.mcw-fp-settings-note' );
      var key = $input.val();

      var btnDisable = function() {
        $( '.fp-extension-install' ).prop( 'disabled', true );
        $input.prop( 'disabled', true );
      };
      var btnEnable = function() {
        $( '.fp-extension-install' ).prop( 'disabled', false );
        $input.prop( 'disabled', false );
      };
      var setMessage = function( msg ) {
        $note.html( $note.html() + msg );
      };

      $note.show();

      if ( key.length < 5 ) {
        setMessage( '<span style="color:red;">Please fill the license key!</span>' );
        return;
      }

      $note.html('Please wait while installing the extension...');

      // Disable button
      btnDisable();

      $.ajax( {
        type: 'POST',
        dataType: 'json',
        url: McwFullPageSettings.ajaxurl,
        data:( {
            action: 'fullpage_extension_install',
            key: encodeURIComponent( key ),
            extension: encodeURIComponent( $button.data( 'extension' ) ),
            nonce: McwFullPageSettings.nonce,
        } ),

        success: function( response ) {
          if ( response && response.success === true ) {
            setMessage( '<br/>' + response.message );
            setMessage( '<br/>Please wait while refreshing the page!' );
            window.location.reload();
          } else {
            setMessage( '<br/><span style="color:red;">Error while installing the plugin!</span><br/><span style="color:red;">' + response.message + '</span>' );
            btnEnable();
          }
        },

        error: function( response ) {
          btnEnable();
          setMessage( '<br/><span style="color:red;">The extension CANNOT be installed!</span><br/><span style="color:red;">Trouble with the server!</span>' );
          console.log( response );
        }
      } );
    } );
  } );
} )( jQuery );
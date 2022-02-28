( function( $ ) {
  "use strict";

  jQuery( document ).ready( function() {
    if ( window.elementor !== undefined ) {
      var getId = function( suffix ) {
        return 'mcw-fullpage-elementor-' + suffix;
      }

      var getConfig = function( id ) {
        return elementor.settings.page && elementor.settings.page.model.get( getId( id ) );
      };

      if ( typeof elementor.settings.page != "undefined" ) {
        var saver = function( newValue ) {
          var isNew = "2.9.0".localeCompare( elementor.config.version, undefined, { numeric: true, sensitivity: 'base' } );
          if (isNew > 0) {
            elementor.saver.update( {
              onSuccess: function() {
                elementor.reloadPreview();
              }
            } );
          } else {
            $e.run( 'document/save/update' ).then( function(){
              elementor.reloadPreview();
            } );
          }
        };

        // Enabled and enable template options call saver
        elementor.settings.page.addChangeCallback( getId( 'enabled' ), saver);
        elementor.settings.page.addChangeCallback( getId( 'enable-template' ), saver);

        elementor.settings.page.addChangeCallback( getId( 'vertical-alignment' ), function( newValue ) {
          elementor.$previewContents.find('body').attr( 'data-fp-align', newValue );
        } );

        elementor.on( 'preview:loaded', function() {
          elementor.$previewContents.find('body').attr( 'data-fp-align', getConfig( 'vertical-alignment' ) );
        } );
      }

      elementor.hooks.addAction( 'panel/open_editor/section', function( panel, model, view ) {
        // Get section settings example
        // model.getSetting( getId( 'section-behaviour' ) )

        var settingsModel = model.get('settings');
        var getOption = function( id, oModel = settingsModel ) {
          return oModel.get( getId( id ) );
        };
        var setOption = function( id, val, oModel = settingsModel, oPanel = panel ) {
          id = getId( id );
          oModel.set( id, val );
          oPanel.$el.find('input[data-setting="' + id + '"]').val( val );
          return id;
        };
        var setChanged = function( id, val, oModel = settingsModel ) {
          if ( getOption( id, oModel ) !== val ) {
            id = setOption( id, val, oModel );
          }
        };

        var render = getConfig( 'enabled' );

        setChanged( 'section-is-inner', settingsModel.get( 'isInner' ) ? true : false );
        setChanged( 'section-no-render', ! render );
        setChanged( 'enable-data-percentage', getConfig( 'enable-extensions' ) === 'yes' && getConfig( 'extension-offset-sections' ) === 'yes' );
        setChanged( 'enable-data-drop', getConfig( 'enable-extensions' ) === 'yes' && getConfig( 'extension-drop-effect' ) === 'yes' );

        if ( render ) {
          view.listenTo( settingsModel, 'change', function( changeModel ) {
            if ( changeModel.hasChanged( getId( 'section-anchor' ) ) ) {
              setChanged( 'section-anchor', getOption( 'section-anchor', changeModel ).replace( /[^a-zA-Z0-9-_]/g, '' ) );
            }
            if ( changeModel.hasChanged( getId( 'slide-anchor' ) ) ) {
              setChanged( 'slide-anchor', getOption( 'slide-anchor', changeModel ).replace( /[^a-zA-Z0-9-_]/g, '' ) );
            }
          } )
        }
      } );
    }
  } );
} ( jQuery ) );

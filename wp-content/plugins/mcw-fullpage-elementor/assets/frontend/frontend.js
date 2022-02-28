( function( $ ) {
  "use strict";

  var getId = function( suffix ) {
    return 'mcw-fullpage-elementor-' + suffix;
  }

  $( window ).on( 'elementor/frontend/init', function() {
    var FrontEndExtended = elementorModules.frontend.handlers.Base.extend( {
      getFieldValue: function( option, raw = false ) {
        var modelCID = this.getModelCID();
        option = raw ? option : getId( option );
        if ( modelCID ) {
          var settings = elementorFrontend.config.elements.data[ modelCID ];
          if ( settings ) {
            return settings.get( option );
          }
        }

        return this.getElementSettings( option );
      },

      isFieldOn: function( option ) {
        return this.getFieldValue( option ) === 'yes' ? true : false;
      },

      isFullPageEnabled: function() {
        return elementorFrontend.getPageSettings( getId( 'enabled' ) ) === 'yes';
      },

      isInner: function() {
        return this.getFieldValue( 'isInner', true )
      },

      run: function() {
        // FullPage is not enabled
        if ( ! this.isFullPageEnabled() ) {
          return;
        }

        if ( ! this.getModelCID() ) {
          return;
        }

        if ( this.isInner() ) {
          this.$element.addClass( 'mcw-fp-slide' );
          if ( elementorFrontend.getPageSettings( getId( 'disable-anchors' ) ) !== 'yes' ) {
            var anchor = this.getFieldValue( 'slide-anchor' );
            if ( anchor ) {
              this.$element.attr( 'data-anchor', anchor );
            } else {
              this.$element.removeAttr( 'data-anchor' );
            }
          } else {
            this.$element.removeAttr( 'data-anchor' );
          }
        } else {
          this.$element.addClass( 'mcw-fp-section' );
          if ( this.isFieldOn( 'section-is-slide' ) ) {
            this.$element.addClass( 'mcw-fp-section-slide' );
          } else {
            this.$element.removeClass( 'mcw-fp-section-slide' );
          }

          var sectionBehaviour = this.getFieldValue( 'section-behaviour' );
          if ( sectionBehaviour === 'auto' ) {
            this.$element.addClass( 'fp-auto-height' );
            this.$element.removeClass( 'fp-auto-height-responsive' );
          } else if ( sectionBehaviour === 'responsive' ) {
            this.$element.removeClass( 'fp-auto-height' );
            this.$element.addClass( 'fp-auto-height-responsive' );
          } else {
            this.$element.removeClass( 'fp-auto-height' );
            this.$element.removeClass( 'fp-auto-height-responsive' );
          }

          if ( this.isFieldOn( 'section-scrollbars' ) ) {
            this.$element.addClass( 'fp-noscroll' );
          } else {
            this.$element.removeClass( 'fp-noscroll' );
          }

          if ( elementorFrontend.getPageSettings( getId( 'disable-anchors' ) ) !== 'yes' ) {
            var anchor = this.getFieldValue( 'section-anchor' );
            if ( ! anchor ) {
              anchor = 'section-' + this.getID();
            }
            this.$element.attr( 'data-anchor', anchor );
          } else {
            this.$element.removeAttr( 'data-anchor' );
          }

          var tooltip = this.getFieldValue( 'section-nav-tooltip' );
          if ( tooltip ) {
            this.$element.attr( 'data-tooltip', tooltip );
          }
        }
      },

      onElementChange: function( option ) {
        this.run();
      },

      onInit: function() {
        elementorModules.frontend.handlers.Base.prototype.onInit.apply(this, arguments);
        this.run();
      },

      onDestroy: function() {
        elementorModules.frontend.handlers.Base.prototype.onDestroy.apply(this, arguments);
      }
    } );

    elementorFrontend.hooks.addAction( 'frontend/element_ready/section', function( $element ) {
      if ( 'section' === $element.data( 'element_type' ) ) {
        new FrontEndExtended( {
            $element: $element
        } );
      }
    } );
  } );

} ( jQuery ) );
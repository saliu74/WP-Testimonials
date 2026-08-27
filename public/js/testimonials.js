/**
 * Testimonials Manager — front-end script.
 *
 * Vanilla JavaScript, no framework dependency, kept deliberately small.
 * Handles: responsive/touch/keyboard-accessible carousel with optional
 * autoplay, and AJAX-enhanced grid pagination that degrades gracefully to
 * plain links when JavaScript is unavailable.
 */
/* global tmTestimonials */
( function () {
	'use strict';

	if ( 'loading' === document.readyState ) {
		document.addEventListener( 'DOMContentLoaded', init );
	} else {
		init();
	}

	function init() {
		var carousels = document.querySelectorAll( '.tm-testimonial-carousel' );
		for ( var i = 0; i < carousels.length; i++ ) {
			new TMCarousel( carousels[ i ] ); // eslint-disable-line no-new
		}

		var grids = document.querySelectorAll( '.tm-testimonial-grid[data-tm-pagination="true"]' );
		for ( var j = 0; j < grids.length; j++ ) {
			initGridPagination( grids[ j ] );
		}
	}

	var prefersReducedMotion = window.matchMedia && window.matchMedia( '(prefers-reduced-motion: reduce)' ).matches;

	/**
	 * A single carousel instance.
	 *
	 * @param {HTMLElement} root Root `.tm-testimonial-carousel` element.
	 */
	function TMCarousel( root ) {
		this.root = root;
		this.viewport = root.querySelector( '.tm-carousel-viewport' );
		this.track = root.querySelector( '.tm-carousel-track' );
		this.slides = Array.prototype.slice.call( root.querySelectorAll( '.tm-carousel-slide' ) );
		this.prevBtn = root.querySelector( '.tm-carousel-prev' );
		this.nextBtn = root.querySelector( '.tm-carousel-next' );
		this.playPauseBtn = root.querySelector( '.tm-carousel-playpause' );
		this.dotsWrap = root.querySelector( '.tm-carousel-dots' );
		this.live = root.querySelector( '.tm-carousel-live' );

		this.index = 0;
		this.autoplay = 'true' === root.getAttribute( 'data-autoplay' ) && ! prefersReducedMotion;
		this.interval = parseInt( root.getAttribute( 'data-interval' ), 10 ) || 5000;
		this.slidesDesktop = parseInt( root.getAttribute( 'data-slides-desktop' ), 10 ) || 3;
		this.slidesTablet = parseInt( root.getAttribute( 'data-slides-tablet' ), 10 ) || 2;
		this.slidesMobile = parseInt( root.getAttribute( 'data-slides-mobile' ), 10 ) || 1;
		this.timer = null;
		this.playing = this.autoplay;

		this.setResponsiveVars();
		this.buildDots();
		this.bindEvents();
		this.goTo( 0, true );

		if ( this.playing ) {
			this.startAutoplay();
		}
	}

	TMCarousel.prototype.setResponsiveVars = function () {
		this.root.style.setProperty( '--tm-slides', this.slidesDesktop );
		this.root.style.setProperty( '--tm-slides-tablet', this.slidesTablet );
		this.root.style.setProperty( '--tm-slides-mobile', this.slidesMobile );
	};

	TMCarousel.prototype.getSlidesPerView = function () {
		var width = window.innerWidth;
		if ( width <= 640 ) {
			return this.slidesMobile;
		}
		if ( width <= 1024 ) {
			return this.slidesTablet;
		}
		return this.slidesDesktop;
	};

	TMCarousel.prototype.maxIndex = function () {
		var perView = this.getSlidesPerView();
		return Math.max( 0, this.slides.length - perView );
	};

	TMCarousel.prototype.buildDots = function () {
		if ( ! this.dotsWrap ) {
			return;
		}

		var self = this;
		var perView = this.getSlidesPerView();
		var dotCount = Math.max( 1, this.slides.length - perView + 1 );

		this.dotsWrap.innerHTML = '';
		this.dots = [];

		for ( var i = 0; i < dotCount; i++ ) {
			var btn = document.createElement( 'button' );
			btn.type = 'button';
			btn.setAttribute( 'role', 'tab' );
			btn.setAttribute( 'aria-selected', i === this.index ? 'true' : 'false' );
			btn.setAttribute( 'aria-label', 'Go to slide ' + ( i + 1 ) );
			( function ( slideIndex ) {
				btn.addEventListener( 'click', function () {
					self.goTo( slideIndex );
					self.pauseForInteraction();
				} );
			} )( i );
			this.dotsWrap.appendChild( btn );
			this.dots.push( btn );
		}
	};

	TMCarousel.prototype.updateDots = function () {
		if ( ! this.dots ) {
			return;
		}
		for ( var i = 0; i < this.dots.length; i++ ) {
			this.dots[ i ].setAttribute( 'aria-selected', i === this.index ? 'true' : 'false' );
		}
	};

	TMCarousel.prototype.bindEvents = function () {
		var self = this;

		if ( this.prevBtn ) {
			this.prevBtn.addEventListener( 'click', function () {
				self.prev();
				self.pauseForInteraction();
			} );
		}
		if ( this.nextBtn ) {
			this.nextBtn.addEventListener( 'click', function () {
				self.next();
				self.pauseForInteraction();
			} );
		}
		if ( this.playPauseBtn ) {
			this.playPauseBtn.addEventListener( 'click', function () {
				self.toggleAutoplay();
			} );
		}

		this.root.addEventListener( 'keydown', function ( e ) {
			if ( 'ArrowLeft' === e.key ) {
				self.prev();
				self.pauseForInteraction();
			} else if ( 'ArrowRight' === e.key ) {
				self.next();
				self.pauseForInteraction();
			}
		} );

		// Pause on hover/focus for readability, resume on leave.
		this.root.addEventListener( 'mouseenter', function () {
			self.stopAutoplay( true );
		} );
		this.root.addEventListener( 'mouseleave', function () {
			if ( self.playing ) {
				self.startAutoplay();
			}
		} );
		this.root.addEventListener( 'focusin', function () {
			self.stopAutoplay( true );
		} );
		this.root.addEventListener( 'focusout', function () {
			if ( self.playing ) {
				self.startAutoplay();
			}
		} );

		// Touch swipe support.
		var startX = null;
		this.viewport.addEventListener( 'touchstart', function ( e ) {
			startX = e.touches[ 0 ].clientX;
		}, { passive: true } );
		this.viewport.addEventListener( 'touchend', function ( e ) {
			if ( null === startX ) {
				return;
			}
			var deltaX = e.changedTouches[ 0 ].clientX - startX;
			if ( Math.abs( deltaX ) > 40 ) {
				if ( deltaX < 0 ) {
					self.next();
				} else {
					self.prev();
				}
				self.pauseForInteraction();
			}
			startX = null;
		} );

		window.addEventListener( 'resize', debounce( function () {
			self.buildDots();
			self.goTo( Math.min( self.index, self.maxIndex() ), true );
		}, 200 ) );
	};

	TMCarousel.prototype.goTo = function ( index, skipAnnounce ) {
		var max = this.maxIndex();
		this.index = Math.max( 0, Math.min( index, max ) );

		var perView = this.getSlidesPerView();
		var percentPerSlide = 100 / perView;
		this.track.style.transform = 'translateX(-' + ( this.index * percentPerSlide ) + '%)';

		this.updateDots();

		if ( this.live && ! skipAnnounce ) {
			var current = this.slides[ this.index ];
			var label = current ? current.getAttribute( 'aria-label' ) : '';
			this.live.textContent = label || '';
		}
	};

	TMCarousel.prototype.next = function () {
		var max = this.maxIndex();
		this.goTo( this.index >= max ? 0 : this.index + 1 );
	};

	TMCarousel.prototype.prev = function () {
		var max = this.maxIndex();
		this.goTo( this.index <= 0 ? max : this.index - 1 );
	};

	TMCarousel.prototype.startAutoplay = function () {
		var self = this;
		this.stopAutoplay();
		this.timer = window.setInterval( function () {
			self.next();
		}, this.interval );
	};

	TMCarousel.prototype.stopAutoplay = function () {
		if ( this.timer ) {
			window.clearInterval( this.timer );
			this.timer = null;
		}
	};

	TMCarousel.prototype.pauseForInteraction = function () {
		// A manual interaction doesn't permanently stop autoplay (that's
		// what the explicit play/pause button is for) — it just resets
		// the timer so the next auto-advance isn't jarringly soon.
		if ( this.playing ) {
			this.startAutoplay();
		}
	};

	TMCarousel.prototype.toggleAutoplay = function () {
		this.playing = ! this.playing;

		if ( this.playing ) {
			this.startAutoplay();
			this.playPauseBtn.setAttribute( 'aria-pressed', 'true' );
			this.playPauseBtn.setAttribute( 'aria-label', ( tmTestimonials.i18n && tmTestimonials.i18n.pause ) || 'Pause autoplay' );
		} else {
			this.stopAutoplay();
			this.playPauseBtn.setAttribute( 'aria-pressed', 'false' );
			this.playPauseBtn.setAttribute( 'aria-label', ( tmTestimonials.i18n && tmTestimonials.i18n.play ) || 'Play autoplay' );
		}
	};

	/**
	 * AJAX-enhance grid pagination links. Falls back to normal navigation
	 * if the request fails, and works with no JS at all since the links
	 * are real URLs.
	 *
	 * @param {HTMLElement} grid Root `.tm-testimonial-grid` element.
	 */
	function initGridPagination( grid ) {
		var atts = {};
		try {
			atts = JSON.parse( grid.getAttribute( 'data-tm-atts' ) || '{}' );
		} catch ( e ) {
			return;
		}

		grid.addEventListener( 'click', function ( e ) {
			var link = e.target.closest ? e.target.closest( '.tm-page-link' ) : null;
			if ( ! link || ! grid.contains( link ) ) {
				return;
			}

			e.preventDefault();
			var page = parseInt( link.getAttribute( 'data-tm-page' ), 10 ) || 1;
			loadGridPage( grid, atts, page, link.href );
		} );
	}

	function loadGridPage( grid, atts, page, fallbackUrl ) {
		grid.classList.add( 'is-loading' );

		var body = new URLSearchParams();
		body.append( 'action', 'tm_load_grid_page' );
		body.append( 'nonce', tmTestimonials.nonce );
		body.append( 'atts', JSON.stringify( atts ) );
		body.append( 'paged', page );

		fetch( tmTestimonials.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: body
		} )
			.then( function ( response ) {
				return response.json();
			} )
			.then( function ( json ) {
				grid.classList.remove( 'is-loading' );

				if ( ! json.success ) {
					window.location.href = fallbackUrl;
					return;
				}

				var itemsWrap = grid.querySelector( '.tm-grid-items' );
				if ( itemsWrap ) {
					itemsWrap.innerHTML = json.data.html;
				}

				// Move focus to the top of the grid for keyboard/screen
				// reader users so pagination doesn't strand their position.
				grid.setAttribute( 'tabindex', '-1' );
				grid.focus( { preventScroll: false } );

				updatePaginationControls( grid, atts, page, json.data.max_pages );
			} )
			.catch( function () {
				grid.classList.remove( 'is-loading' );
				window.location.href = fallbackUrl;
			} );
	}

	function updatePaginationControls( grid, atts, page, maxPages ) {
		var nav = grid.querySelector( '.tm-grid-pagination' );
		if ( ! nav ) {
			return;
		}

		var statusEl = nav.querySelector( '.tm-page-status' );
		if ( statusEl ) {
			statusEl.textContent = 'Page ' + page + ' of ' + maxPages;
		}

		var prevLink = nav.querySelector( '.tm-page-prev' );
		var nextLink = nav.querySelector( '.tm-page-next' );

		if ( prevLink ) {
			if ( page <= 1 ) {
				prevLink.remove();
			} else {
				prevLink.setAttribute( 'data-tm-page', page - 1 );
			}
		}
		if ( nextLink ) {
			if ( page >= maxPages ) {
				nextLink.remove();
			} else {
				nextLink.setAttribute( 'data-tm-page', page + 1 );
			}
		}
	}

	/**
	 * Minimal debounce helper (no lodash dependency).
	 */
	function debounce( fn, wait ) {
		var timeout;
		return function () {
			var args = arguments;
			var context = this;
			window.clearTimeout( timeout );
			timeout = window.setTimeout( function () {
				fn.apply( context, args );
			}, wait );
		};
	}
} )();

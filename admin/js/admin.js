( function ( $, window, document ) {
	'use strict';
	$( document ).ready( function () {
		
		var ajaxURL = craat_obj.ajax_url;
		var craatadmin = $( '#craatWrapper' );
		
		if ( craatadmin.length == 0 ) {
			return;
		}
		
		$( '#admin-view-form' ).submit( function( e ) {
			
			e.preventDefault();

			var api_key            = $( this ).find( '#api_key' ).val();
			var keyword_seeding    = $( this ).find( '#keyword_seeding' ).val();
			var language           = $( this ).find( '#language' ).val();
			var cron_timeout       = $( this ).find( '#cron_timeout' ).val();
			var generate_on_save   = $( this ).find( '#generate_on_save' ).is( ':checked' );
			var cron_enabled       = $( this ).find( '#cron_enabled' ).is( ':checked' );
			var enable_logging     = $( this ).find( '#enable_logging' ).is( ':checked' );

			$.ajax( {
				method: 'POST',
				url: ajaxURL,
				cache: false,
				data:{
					action: 'craat_save_admin_page',
					api_key: api_key,
					keyword_seeding: keyword_seeding,
					language: language,
					cron_timeout: cron_timeout,
					generate_on_save: generate_on_save,
					cron_enabled: cron_enabled,
					enable_logging: enable_logging
				},
				success: function( response ) {
					location.reload();
				}
			} );
			
		} );

		// logs

		var logs_page = 0;
		var logs_pages = 0;

		function createLogTable ( data ) {
			logs_pages = data.max == 0 ? 0 : Math.floor( data.max / data.count );
			craatadmin.find( '.logs-start' ).prop( "disabled", logs_pages == 0 || logs_page == 0 );
			craatadmin.find( '.logs-prev' ).prop( "disabled", logs_pages == 0 || logs_page == 0 );
			craatadmin.find( '.logs-next' ).prop( "disabled", logs_pages == 0 || data.index + data.count >= data.max );
			craatadmin.find( '.logs-end' ).prop( "disabled", logs_pages == 0 || logs_page == logs_pages );
			craatadmin.find( '#logs .meta .page-index' ).text( logs_page + 1 );
			craatadmin.find( '#logs .meta .page-count' ).text( logs_pages + 1 );
			craatadmin.find( '#logs tbody tr:not( .seed )' ).remove();
			var seed = craatadmin.find( '#logs tbody tr.seed' );
			for ( var i = 0; i < data.rows.length; i++ ) {
				var url = data.rows[ i ];
				var clone = seed.clone( true );
				clone.removeClass( 'seed' );
				clone.find( '.timestamp' ).text( new Date( url[0] * 1000 ).toLocaleString()  );
				clone.find( '.logdata' ).text( url[1] );
				clone.find( '.button-delete' ).attr( 'data-url', url[0] );
				clone.find( '.button-delete' ).data( 'url', url[0] );
				craatadmin.find( '#logs tbody' ).append( clone );
			}
		}

		function getLogData () {
			$.ajax( {
				method: 'POST',
				url: ajaxURL,
				cache: false,
				data:{
					page: logs_page,
					action: 'craat_logs_get_page'
				},
				success: function( response ) {
					$( '#logs' ).removeClass( 'loading' );
					createLogTable( response.data );
				}
			} );
		}

		craatadmin.find( '.logs-clear' ).click( function( e ) {
			if ( confirm( $( this ).data( 'confirm' ) ) == true ) {
				$( '#logs' ).addClass( 'loading' );
				$.ajax( {
					method: 'POST',
					url: ajaxURL,
					cache: false,
					data:{
						action: 'craat_logs_clear'
					},
					success: function( response ) {
						$( '#logs' ).removeClass( 'loading' );
						createLogTable( response.data );
					}
				} );
			}
			e.preventDefault();
			return false;
		} );

		craatadmin.find( '.logs-refresh' ).click( function( e ) {
			$( '#logs' ).addClass( 'loading' );
			getLogData();
			e.preventDefault();
			return false;
		} );

		craatadmin.find( '.logs-start' ).click( function( e ) {
			$( '#logs' ).addClass( 'loading' );
			logs_page = 0;
			getLogData();
			e.preventDefault();
			return false;
		} );

		craatadmin.find( '.logs-prev' ).click( function( e ) {
			$( '#logs' ).addClass( 'loading' );
			logs_page -= 1;
			getLogData();
			e.preventDefault();
			return false;
		} );

		craatadmin.find( '.logs-next' ).click( function( e ) {
			$( '#logs' ).addClass( 'loading' );
			logs_page += 1;
			getLogData();
			e.preventDefault();
			return false;
		} );

		craatadmin.find( '.logs-end' ).click( function( e ) {
			$( '#logs' ).addClass( 'loading' );
			logs_page = logs_pages;
			getLogData();
			e.preventDefault();
			return false;
		} );

		// charting

		var chart_data = 0;
		var chart;
		
		function drawChart() {
			document.querySelector("#chart").classList.remove("loading");

			var rows = [];

			for ( var key in chart_data ) {
				if ( Object.hasOwnProperty.call( chart_data, key ) ) {
					var value    = chart_data[key];
					var key_bits = key.split( '-' );
					if ( key_bits.length == 4 ) {
						var hour = key_bits.pop();
						key = key_bits.join( '-' ) + " " + hour + ":00:00";
					}
					rows.push( [new Date( key ), value ] );
				}
			}

			// Check if the chart already exists and destroy it
			if (typeof chart !== 'undefined' && chart !== null) {
				chart.destroy(); // Destroy the existing chart before re-rendering
			}

			// Prepare ApexCharts options for the events chart
			var options = {
				chart: {
					type: 'line',
					height: 250,
					toolbar: { show: false },
					animations: { enabled: false },
				},
				series: [{
					name: 'Total',
					data: rows
				}],
				xaxis: {
					type: 'datetime'
				},
				colors: ['#000000'],
				stroke: {
					width: 2
				},
				legend: {
					position: 'bottom'
				},
			};

			// Render the events chart in the specified div
			chart = new ApexCharts(document.querySelector("#chart_div"), options);
			chart.render();
		}

		function loadChartData () {
			$( '#chart' ).addClass( 'loading' );
			$.ajax( {
				method: 'POST',
				url: ajaxURL,
				cache: false,
				data:{
					action: 'craat_get_stats_data',
					timespan: $( '#chart-timespan' ).val()
				},
				success: function( response ) {
					chart_data = response.data;
					drawChart();
				}
			} );
		}

		craatadmin.find( '#chart-timespan' ).change( loadChartData );

		// tabs
		var tabs         = craatadmin.find( '.tabs > li' );
		var tabs_content = craatadmin.find( '.tab__content > li' );
		var page_hash    = window.location.hash == '' ? tabs.first().data( 'tab' ) : window.location.hash.substr( 1 );
		
		function setCurrentTab ( hash ) {
			tabs.each( function () {
				if ( $( this ).data( 'tab' ) == hash ) {
					tabs.removeClass( 'active' );
					$( this ).addClass( 'active' );
					tabs_content.removeClass( 'active' );
					$( '#tab-' + hash ).addClass( 'active' );
				}
			} );
			window.location.hash = hash;
			if ( hash == 'log' ) {
				getLogData();
			} else if ( hash == 'stats' ) {
				loadChartData();
			}
		}
		
		tabs.click( function( e ) {
			if ( $( this ).hasClass( 'disabled' ) ) {
				return;
			}
			setCurrentTab( $( this ).data( 'tab' ) );
		} );
		
		setCurrentTab( page_hash );
	
	});
} ( jQuery, window, document ) );
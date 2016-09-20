/**
 * F(X) BUILDER JS: ROWS
*************************************/

 
/* Functions
------------------------------------------ */
;(function($){

	/**
	 * UPDATE ROWS INDEX
	 *
	 * Even though sortable provide row ids, it's best to parse each row using each() because:
	 * - There's only one rows level.
	 * - we need to update html element in each row.
	 *
	 * This function should be loaded on:
	 * - Add new row
	 * - Delete row
	 * - Sort row
	 *
	 ************************************
	 */
	$.fn.fxB_updateRowsIndex = function() {

		/* Var Row IDs */
		var row_ids = [];

		/* Update each rows attr */
		$( '#fxb > .fxb-row' ).each( function(i){

			/* Var */
			var num = i + 1;
			var row_id = $( this ).data( 'id' );

			/* Set data */
			$( this ).data( 'index', num ); // set index
			var row_index = $( this ).data( 'index' ); // get index

			/* Update Row */
			$( this ).attr( 'data-index', row_index ); // set data attr
			$( this ).find( '.fxb_row_index' ).text( row_index ); // display text
			$( this ).find( 'input[data-row_field="index"]' ).val( row_index ); // change input

			/* Get ID */
			row_ids.push( row_id );
		});

		/* Update Hidden Input */
		$( 'input[name="_fxb_row_ids"]' ).val( row_ids.join() );
	};

})(jQuery);


/* Document Ready
------------------------------------------ */
jQuery(document).ready(function($){

	/**
	 * VAR
	 * 
	 ************************************
	 */
	var row_template = wp.template( 'fxb-row' ); // load #tmpl-fxb-row


	/**
	 * ADD NEW ROW
	 *
	 * Add new row when click new row button.
	 * 
	 ************************************
	 */
	$( document.body ).on( 'click', '.fxb-add-row', function(e){
		e.preventDefault();

		/* Var */
		var row_id = new Date().getTime(); // time stamp when crating row

		/* Add template to container */
		$( '#fxb' ).prepend( row_template( {
			id          : row_id,
			index       : '1',
			state       : 'open',
			layout      : '1',
			col_num     : '1',
			col_order   : '',
			col_1       : '',
			col_2       : '',
			col_3       : '',
			col_4       : '',
		} ) );

		/* Update Index */
		$.fn.fxB_updateRowsIndex();

		/* Make New Column Sortable */
		$.fn.fxB_sortItems();

	} );


	/**
	 * REMOVE ROW
	 *
	 * Delete row when click new row button.
	 * With confirmation message.
	 * 
	 ************************************
	 */
	$( document.body ).on( 'click', '.fxb-remove-row', function(e){
		e.preventDefault();

		/* Confirm delete */
		var confirm_delete = confirm( $( this ).data( 'confirm' ) );
		if ( true ===  confirm_delete ){

			/* Remove Row */
			$( this ).parents( '.fxb-row' ).remove();

			/* Update Index */
			$.fn.fxB_updateRowsIndex();
		}
	} );


	/**
	 * TOGGLE ROW STATE
	 *
	 * Open/Close Row using toggle arrow icon.
	 * 
	 ************************************
	 */
	$( document.body ).on( 'click', '.fxb-toggle-row', function(e){
		e.preventDefault();

		/* Var */
		var row = $( this ).parents( '.fxb-row' );
		var row_state = row.data( 'state' ); // old

		/* Toggle state data */
		if( 'open' == row_state ){
			row.data( 'state', 'close' ); // set data
		}
		else{
			row.data( 'state', 'open' );
		}

		/* Update state */
		var row_state = row.data( 'state' ); // get new state data
		row.attr( 'data-state', row_state ); // change attr for styling
		row.find( 'input[data-row_field="state"]' ).val( row_state ); // change hidden input
	} );


	/**
	 * OPEN/CLOSE ROW SETTINGS
	 * 
	 ************************************
	 */
	/* == Open settings == */
	$( document.body ).on( 'click', '.fxb-settings', function(e){
		e.preventDefault();

		/* Show settings target */
		$( this ).siblings( $( this ).data( 'target' ) ).show();

		/* Show overlay background */
		$( '.fxb-modal-overlay' ).show();
	} );

	/* == Close Settings == */
	$( document.body ).on( 'click', '.fxb-row-settings .fxb-modal-close', function(e){
		e.preventDefault();

		/* Hide Settings Modal */
		$( this ).parents( '.fxb-modal' ).hide();

		/* Hide overlay background */
		$( '.fxb-modal-overlay' ).hide();
	} );


	/**
	 * ROW SETTINGS: CHANGE LAYOUT
	 * 
	 ************************************
	 */
	$( document.body ).on( 'change', 'select[data-row_field="layout"]', function(e){

		/* Get selected value */
		var new_layout = $( this ).val();
		var new_col_num = $( 'option:selected', this ).attr( 'data-col_num' );
		console.log( new_layout );

		/* Get current row */
		var row = $( this ).parents( '.fxb-row' );

		/* Update Row Data */
		row.data( 'layout', new_layout ); // set layout
		row.attr( 'data-layout', row.data( 'layout' ) ); // update data attr
		row.data( 'col_num', new_col_num );
		row.attr( 'data-col_num', row.data( 'col_num' ) );

		/* Update hidden Input */
		row.find( 'input[data-row_field="col_num"]' ).val( row.data( 'col_num' ) );
	} );


	/**
	 * ROW SETTINGS: CHANGE COLUMNS COLLAPSE ORDER
	 * 
	 ************************************
	 */
	$( document.body ).on( 'change', 'select[data-row_field="col_order"]', function(e){

		/* Get selected value */
		var selected = $( this ).val();

		/* Get current row */
		var row = $( this ).parents( '.fxb-row' );

		/* Update Row Data */
		row.data( 'col_order', selected );
		row.attr( 'data-col_order', row.data( 'col_order' ) );
	} );


	/**
	 * SORT ROW
	 * 
	 * Make row sortable.
	 * 
	 ************************************
	 */
	$( '#fxb' ).sortable({
		handle  : '.fxb-row-handle',
		cursor  : 'grabbing',
		axis    : 'y',
		stop    : function( e, ui ) {
			$.fn.fxB_updateRowsIndex();
		},
	});

});
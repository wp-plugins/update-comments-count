;jQuery(document).ready(function($) {



	var be_ucc_index, be_ucc_pack, be_ucc_count, be_ucc_nonce, be_ucc_cancel;



	$('#be-ucc-input').submit(function() {

		be_ucc_index 	= 0;
		be_ucc_cancel 	= false;
		be_ucc_pack 	= parseInt($('#be-ucc-pack').val(), 10);
		be_ucc_count 	= parseInt($('#be-ucc-count').val(), 10);
		be_ucc_nonce 	= $('#be-ucc-nonce').val();
		
		if (be_ucc_count) {
			$(this).hide();
			$('#be-ucc-progress-wrapper').show();
			$('#be-ucc-progress').attr('style', 'padding-left: 20px; background: url(images/loading.gif) left top no-repeat');
			$.event.trigger({type : 'update.meta.pawc'});
		}
		
		return false;
	});



	$(document).on('update.meta.pawc', function(e) {
		
		var progress = ((be_ucc_index + be_ucc_pack > be_ucc_count)? be_ucc_count : be_ucc_index + be_ucc_pack) + '/' + be_ucc_count;
		
		if (be_ucc_cancel) {
			$('#be-ucc-progress').attr('style', 'padding-left: 20px; background: url(images/no.png) left top no-repeat');			
			$('#be-ucc-progress').html(be_ucc_message_r('cancelled', progress) + ' - <a id="be-ucc-continue" href="#">' + be_ucc_message('continue') + '</a>');
			
		} else {
		
			$('#be-ucc-progress').html(be_ucc_message_r('updating', progress) + ' - <span><a id="be-ucc-cancel" href="#">' + be_ucc_message('cancel') + '</a></span>');

			$.post(ajaxurl, {'action' : $('#be-ucc-action').val(), 'nonce' : be_ucc_nonce, 'index' : be_ucc_index}, function(e) {
				
				if ('undefined' == typeof e || 'undefined' == typeof e.status || 'ok' != e.status) {
					alert('Error: ' + e.reason);

				} else if (e.ended) {
					$('#be-ucc-progress').attr('style', 'padding-left: 20px; background: url(images/yes.png) left top no-repeat');
					$('#be-ucc-progress').html(be_ucc_message_r('completed', be_ucc_count));
				
				} else {
					be_ucc_nonce = e.nonce;
					be_ucc_index += be_ucc_pack;
					$.event.trigger({type : 'update.meta.pawc'});
				}
			});
		}
	});



	$(document).on('click', '#be-ucc-cancel', function(e) {
		be_ucc_cancel = true;
		$(this).closest('span').html(be_ucc_message('cancelling') + '...');
		return false;
	});



	$(document).on('click', '#be-ucc-continue', function(e) {
		be_ucc_cancel = false;
		$('#be-ucc-progress').attr('style', 'padding-left: 20px; background: url(images/loading.gif) left top no-repeat');
		$.event.trigger({type : 'update.meta.pawc'});
		return false;
	});



	function be_ucc_message(id) {
		return $('#be-ucc-js-' + id).html();
	}



	function be_ucc_message_r(id, value) {
		return $('#be-ucc-js-' + id).html().replace('%value%', value);
	}



});
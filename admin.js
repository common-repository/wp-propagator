<!--



jQuery(document).ready(function($){

	function disable_form()
	{
		$('#ppropagator-new-url').attr('disabled','disabled');
		$('#ppropagator-new-user').attr('disabled','disabled');
		$('#ppropagator-new-pass').attr('disabled','disabled');
		$('input.addsite').attr('disabled','disabled');
		$('.ppropagator-master').attr('disabled','disabled');
		$('#ppropagator_save').attr('disabled','disabled');
	}

	function enable_form()
	{
		$('#ppropagator-new-url').attr('disabled','');
		$('#ppropagator-new-user').attr('disabled','');
		$('#ppropagator-new-pass').attr('disabled','');
		$('input.addsite').attr('disabled','');
		$('.ppropagator-master').attr('disabled','');
		$('#ppropagator_save').attr('disabled','');
	}
	
	$('input.addsite').click(function(){

			if($('#ppropagator-new-url').val()=='http://') {
				alert(ppa['alert_url']);
				return false;
			}
			if($('#ppropagator-new-user').val()=='') {
				alert(ppa['alert_user']);
				return false;
			}
			if($('#ppropagator-new-pass').val()=='') {
				alert(ppa['alert_pass']);
				return false;
			}
			$('img.waiting').css('display','block');
			$.post(ajaxurl, {
				action: 'propagator_add_new', 
				id: $('#propagator_count').val(), 
				url: $('#ppropagator-new-url').val(),
				user: $('#ppropagator-new-user').val(),
				pass: $('#ppropagator-new-pass').val(),
			}, function(element){
				$('table.widefat tbody').append(element).children(':last').hide().fadeIn('normal');
				$('#propagator_count').val( $('#propagator_count').val() + 1 );
				$('img.waiting').css('display','none');
				$('#ppropagator-new-url').val('http://');
				$('#ppropagator-new-user').val('');
				$('#ppropagator-new-pass').val('');
			});
		
	});
	
	// when it first loads get the value from the server
	var data = { action: 'my_special_action3' };
	jQuery.post("admin-ajax.php", data, function(response) {
		if (response == 'checked') 
		{
			$('.mark-as-worker').attr('checked',response);
			disable_form();
		}
		else
		{
			$('.mark-as-worker').attr('checked','');
			enable_form();
		}
	});
	
	$('.mark-as-worker').change(function(){
		if ($(this).attr('checked')) {
			disable_form();
			// change the state via ajax
			var data = {
				action: 'my_special_action2',
				marked_as_worker: 'checked'
			};
			
			jQuery.post("admin-ajax.php", data, function(response) {
				//alert('Got this from the server: ' + response);
			});
		} else {
			enable_form();
			// change the state via ajax
			var data = {
				action: 'my_special_action2',
				marked_as_worker: 'disabled'
			};

			jQuery.post("admin-ajax.php", data, function(response) {
				//alert('Got this from the server: ' + response);
			});
		}
	});
	
	$('a.remove').live('click',function(){
		$(this).parent().parent().parent().parent().remove();
	});
	
	//TODO: Ajax edition
	$('a.edit').live('click',function(){
		
	});

});
-->

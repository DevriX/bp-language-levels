jQuery(document).ready(function($){
	$(document).on('click', '.rm-lang', function(){
		$(this).closest('.lang-container').fadeOut('fast', function(){
			return $(this).remove();
		});
	});

	$(document).on('click', '.add-lang', function(){
		var tpl = $('.lang-tpl').first().clone()
		  , randy = Math.floor((Math.random() * 999) + 11);

		if ( tpl.length == 0 )
			return;

		tpl.removeClass('lang-tpl');

		$('select', tpl).each(function(i,elem){
			$(elem).attr('name', function(){
				return $(this).attr('name').replace(/__id__/g, randy);
			});
		});

		tpl.show().appendTo($('.langs-cont'));

		$('select', tpl).first().focus();
	});

	$('.add-lang').trigger('click');
});
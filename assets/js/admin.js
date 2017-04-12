jQuery(document).ready(function($){
	$(document).on('click', '.lvl-tpl-rm', function(){
		$(this).closest('.lvl-tpl-cont').fadeOut('fast', function(){
			return $(this).remove();
		});
	});

	$(document).on('click', '.add-lvl', function(){
		var tpl = $('.lvl-tpl').first().clone()
		  , randy = Math.floor((Math.random() * 999) + 11);

		if ( tpl.length == 0 )
			return alert('Error, could not find a template!');

		$('input', tpl).each(function(i,elem){
			$(elem).attr('name', function(){
				return $(this).attr('name').replace(/__id__/g, randy);
			});
		});

		tpl.show().appendTo($('.lang-levels'));

		$('input', tpl).first().focus();
	});

	$(document).on('click', '.lang-tpl-rm', function(){
		$(this).closest('.lang-tpl-cont').fadeOut('fast', function(){
			return $(this).remove();
		});
	});

	$(document).on('click', '.add-lang', function(){
		var tpl = $('.lang-tpl').first().clone();

		if ( tpl.length == 0 )
			return alert('Error, could not find a template!');

		tpl.show().appendTo($('.bp-langs'));

		$('input', tpl).first().focus();
	});
});
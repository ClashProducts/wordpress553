
jQuery(function($) {
	if ( 'undefined' == typeof $.fn.pngFix )
		$.fn.pngFix = function() { return this; }

	var thickDims = function() {
		var tbWindow = $('#TB_window');
		var H = $(window).height();
		var W = $(window).width();

		if ( tbWindow.size() ) {
			tbWindow.width( W - 100 ).height( H - 60 );
			$('#TB_iframeContent').width( W - 100 ).height( H - 90 );
			tbWindow.css({'margin-left': '-' + parseInt((( W - 100 ) / 2),10) + 'px','top':'30px','margin-top':'0'});
			if ( $.browser.msie && $.browser.version.substr(0,1) < 7 ) 
				tbWindow.css({'margin-top':document.documentElement.scrollTop+'px'});
		};

		return $('a.thickbox').each( function() {
			var href = $(this).parents('.available-theme').find('.previewlink').attr('href');
			if ( ! href ) return;
			href = href.replace(/&width=[0-9]+/g, '');
			href = href.replace(/&height=[0-9]+/g, '');
			$(this).attr( 'href', href + '&width=' + ( W - 100 ) + '&height=' + ( H - 100 ) );
		});
	};

	thickDims()
	.click( function() {
		var alink = $(this).parents('.available-theme').find('.activatelink');
		var url = alink.attr('href');
		var text = alink.html();

		$('#TB_title').css({'background-color':'#222','color':'#cfcfcf'});
		$('#TB_closeAjaxWindow').css({'float':'left'});
		$('#TB_ajaxWindowTitle').css({'float':'right'})
			.append('&nbsp;<a href="' + url + '" target="_top" class="tb-theme-preview-link">' + text + '</a>');

		$('#TB_iframeContent').width('100%');
		return false;
	} );

	$(window).resize( function() { thickDims() } );
});

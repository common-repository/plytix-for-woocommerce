jQuery(function($){
	// simple multiple select
    $('#rudr_select2_tags').select2();

    /*$('#rudr_select2_tags').on("select2:select", function (evt) {
        var element = evt.params.data.element;
        var $element = $(element);
        
        $element.detach();
        $(this).append($element);
        $(this).trigger("change");
      });*/
    //$('#plytix-settings-field-gtin').select2();
    
    //console.log($('#rudr_select2_tags'));
});
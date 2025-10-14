(function($){
  function initRepeater($wrapper){
    var $items = $wrapper.find('.ysq-repeater-items');
    var template = $wrapper.find('template')[0];
    if (!template) {
      return;
    }
    var index = $items.children().length;

    function bindRemove($item){
      $item.on('click', '.ysq-repeater-remove', function(){
        $(this).closest('.ysq-repeater-item').remove();
      });
    }

    $items.children().each(function(){
      bindRemove($(this));
    });

    $wrapper.find('.ysq-repeater-add').on('click', function(){
      var clone = document.importNode(template.content, true);
      var $clone = $(clone);
      $clone.find('[data-name]').each(function(){
        var fieldName = $(this).data('name');
        $(this).attr('name', $wrapper.data('repeater') + '[' + index + '][' + fieldName + ']');
      });
      $items.append($clone);
      bindRemove($items.children().last());
      index++;
    });
  }

  $(function(){
    $('.ysq-repeater').each(function(){
      initRepeater($(this));
    });
  });
})(jQuery);

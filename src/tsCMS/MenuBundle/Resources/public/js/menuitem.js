$(".routeSelect").on("routeSelected",function(event, data) {
    var input = $(this);
    input.val(data.value);
    if (input.is("[type='hidden']")) {
        var display = $("#"+input.attr("id")+"_display");
        display.val(data.displayvalue);
    }

    input.closest("form").find(".menuitemTitle").val(data.title);
});

$(".menuitemStickyTitle").change(function() {
    var checkbox = $(this);

    checkbox.closest("form").find(".menuitemTitle").prop("readonly",checkbox.is(":checked"));

}).change();
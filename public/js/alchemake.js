function updateHighlights() {
    $userinventory = $("#userinfo_I form");
    if ($userinventory.length === 1) {
        querystring = $userinventory.serialize();
        $.getJSON('/combo/list/',querystring,function(comboinfo) {
            $(".itemqty").closest("tr").removeClass("noticeMessage");
            if (comboinfo.length > 0) {
                $(".itemqty").each(function () {
                    if ($(this).val() > 0) {
                        $(this).closest( "tr" ).addClass("noticeMessage");
                    }
                });
            }
            });
    }
}

$(function () {
    $(".itemqty").change(updateHighlights);
    });
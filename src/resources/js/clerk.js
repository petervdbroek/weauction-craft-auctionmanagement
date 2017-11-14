$(function () {
    $('#defaultMessage').change(function () {
        $.getJSON("/actions/auctionManagement/events/getDefaultMessage?id=" + $(this).val(), function (data) {
            for (var locale in data) {
                $('#messages_' + locale).val(data[locale]);
            }
        });
    });
});
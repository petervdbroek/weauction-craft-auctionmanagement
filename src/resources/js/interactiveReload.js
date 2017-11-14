function changeActiveAuction() {
    var currentActive = $('#activeAuctionId').val();

    // Update auction, don't cache
    $.ajax({
        cache: false,
        url: "https://s3-eu-west-1.amazonaws.com/emta-static.vsrpartners.nl/craft/activeObject_" + $('#event_id').val() + ".json",
        dataType: "json",
        success: function(data) {
            if (data.id && currentActive != data.id) {
                location.reload();
            }
        }
    });
}

$(document).ready(function() {
    setInterval(changeActiveAuction, 7000);
});

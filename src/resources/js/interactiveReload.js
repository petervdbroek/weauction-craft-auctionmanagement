var s3Partition = randomIntFromInterval(0,9);

function changeActiveAuction() {
    var currentActive = $('#activeAuctionId').val();

    // Update auction, don't cache
    $.ajax({
        cache: false,
        url: $('#s3BaseUrl').val() + s3Partition + "/craft/activeObject_" + $('#event_id').val() + ".json",
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

function randomIntFromInterval(min,max)
{
    return Math.floor(Math.random()*(max-min+1)+min);
}

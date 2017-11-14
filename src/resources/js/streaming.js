var SESSION_STATUS = Flashphoner.constants.SESSION_STATUS;
var STREAM_STATUS = Flashphoner.constants.STREAM_STATUS;
var localVideo;

$(function() {
    $('#stream_type').change(function() {
        $('#changeStreamer').submit();
    });

    if ($('#stream_type').val() == 'flashphoner') {
        initAPI();
    }
});

function initAPI() {
    //init api
    try {
        Flashphoner.init({flashMediaProviderSwfLocation: 'media-provider.swf'});
    } catch(e) {
        $("#notifyFlash").text("Your browser doesn't support Flash or WebRTC technology necessary for work of an example");
        return;
    }
    //local and remote displays
    localVideo = document.getElementById("localVideo");
}


//Publish stream
function publishStream() {
    var data = {};
    data[csrfTokenName] = csrfTokenValue;

    Craft.postActionRequest('auctionManagement/streaming/createNewStream', data, function(response) {
        if (response) {
            /*if (response.oldStreamName && response.oldStreamName !== '') {
                f.unPublishStream({name: response.oldStreamName});
            }*/

            start(response.streamName);
        }
    });
}

//Stop stream publishing
function unPublishStream(){
    var data = {};
    data[csrfTokenName] = csrfTokenValue;
    Craft.postActionRequest('auctionManagement/streaming/stopStream', data, function(response) {
        if (response && response.streamName && response.streamName !== '') {
            window.location.reload();
        }
    });
}


function start(streamName) {
    if (Browser.isSafariWebRTC()) {
        Flashphoner.playFirstVideo(localVideo, true);
    }
    //check if we already have session
    if (Flashphoner.getSessions().length > 0) {
        startStreaming(Flashphoner.getSessions()[0], streamName);
    } else {
        var url = $('#urlServer').val();
        console.log("Create new session with url " + url);
        Flashphoner.createSession({urlServer: url}).on(SESSION_STATUS.ESTABLISHED, function(session){
            startStreaming(session, streamName);
        });
    }
}

function startStreaming(session, streamName) {
    var hasVideo = true;
    if ($('#audio_video').val() == 'audio') {
        hasVideo = false;
    }
    session.createStream({
        name: streamName,
        display: localVideo,
        cacheLocalResources: true,
        constraints: {
            video: hasVideo,
            audio: true
        }
    }).publish();
}

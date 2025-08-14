<?php
include_once '../path.php';
include_once '../includes/functions.php';
if(isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') { } else { include '../header.php'; }
?>
<style>
#streams {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 3rem;
}
.stream {

}
.stream .btn {
    margin-top: 1rem;
}
</style>
<main>
    <div class="wrap">
        <div id="streams">
            <div class="stream">
                <h4>MethStreams</h4>
                <div class="info">HD @ 2000 Kbps available</div>
                <a href="https://pre.methstreams.me/schedule/nhl-streams/" target="_blank" class="btn">Watch</a>
            </div>
            <div class="stream">
                <h4>NHL Stream Links</h4>
                <div class="info">HD streams available</div>
                <a href="https://pro.nhlstreamlinks.com/" target="_blank" class="btn">Watch</a>
            </div>
            <div class="stream">
                <h4>NHL Bite</h4>
                <div class="info">HD streams available</div>
                <a href="https://nhlbite.io/" target="_blank" class="btn">Watch</a>
            </div>
            <div class="stream">
                <h4>1Stream</h4>
                <div class="info">HD streams available</div>
                <a href="https://1stream.vip/nhl-streams/" target="_blank" class="btn">Watch</a>
            </div>
        </div>
    </div>
</main>
<script>
    const streams = document.getElementById('streams');
    // Use the new NHL API utility (JavaScript will use the generated URL)
    const ApiUrl = '<?= NHLApi::streams() ?>';
    const xhr = new XMLHttpRequest();
    xhr.open('GET', ApiUrl, true);
    xhr.onload = function() {
        if(this.status === 200) {
            const streamsData = JSON.parse(this.responseText);
            console.log(streamsData);
            streamsData.data.forEach(stream => {
                const streamDiv = document.createElement('div');
                streamDiv.classList.add('stream');
                streamDiv.innerHTML = `
                    <div class="stream-info">
                        <h3>${stream.title}</h3>
                        <p>${stream.description}</p>
                    </div>
                    <div class="stream-video">
                        <iframe src="${stream.embedUrl}" frameborder="0"></iframe>
                    </div>
                `;
                streams.appendChild(streamDiv);
            });
        }
    }
</script>
<?php if(isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {} else { include_once '../footer.php'; } ?>
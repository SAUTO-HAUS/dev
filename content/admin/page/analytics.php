<?php defined('_DOIT') or die('Restricted access'); ?>

<style>
html:has(#analytics-container),
html:has(#analytics-container) body,
html:has(#analytics-container) #main_admin,
html:has(#analytics-container) #content {
    overflow: hidden !important;
    height: 100vh !important;
    max-height: 100vh !important;
    margin: 0 !important;
    padding: 0 !important;
    width: 100% !important;
    box-sizing: border-box !important;
}

html:has(#analytics-container) #content {
    margin-left: 0 !important;
}

html:has(#analytics-container) #menu,
html:has(#analytics-container) #header {
    display: none !important;
}

#analytics-container {
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
}

#analytics-container iframe {
    width: 100%;
    height: 100%;
    border: 0;
    display: block;
}
</style>

<div id="analytics-container">
    <iframe
        src="https://lookerstudio.google.com/embed/reporting/abb9521f-4a9f-4f55-9823-294819c3d224/page/p_pu544nyf0d"
        allowfullscreen>
    </iframe>
</div>

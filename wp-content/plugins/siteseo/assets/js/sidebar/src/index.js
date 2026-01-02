import { registerPlugin } from "@wordpress/plugins"
import { PluginSidebar, PluginSidebarMoreMenuItem } from '@wordpress/editor';
import { useEffect, useState } from '@wordpress/element';
import './sidebar.css';

function sitseo_sidebar(){
    const [content, setContent] = useState();

    useEffect(() => {
        setContent({__html: jQuery('#siteseo-metabox-wrapper')?.prop('outerHTML')});
    }, [])

    return(<>
    <PluginSidebarMoreMenuItem target="siteseo-sidebar">
        SiteSEO
    </PluginSidebarMoreMenuItem>
    <PluginSidebar name="siteseo-sidebar" title="SiteSEO">
        {(content) ? (
                <SiteSEOSideBarHTML content={content}/>
            ) : (<p>Loading here</p>)
        }
    </PluginSidebar>
    </>);
}

function SiteSEOSideBarHTML(props){

    useEffect(() => {
        document.querySelector('#siteseo-sidebar-wrapper tags')?.remove();
    }, [])

    return(<div id="siteseo-sidebar-wrapper" dangerouslySetInnerHTML={props.content}></div>)
}

const siteseo_sidebar_icon = (
        <svg version="1.2" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 956 756" width="32" height="24">
        <defs>
            <linearGradient id="P" gradientUnits="userSpaceOnUse" />
            <linearGradient id="g1" x1="818.4" y1="360.4" x2="621.6" y2="10.3" href="#P">
                <stop stopColor="#00e8d2" />
                <stop offset=".33" stopColor="#00add2" />
                <stop offset="1" stopColor="#4429ff" />
            </linearGradient>
            <linearGradient id="g2" x1="669.3" y1="352.2" x2="490.5" y2="2.2" href="#P">
                <stop stopColor="#00e8d2" />
                <stop offset=".33" stopColor="#00add2" />
                <stop offset="1" stopColor="#4429ff" />
            </linearGradient>
            <linearGradient id="g3" x1="519.4" y1="-222.4" x2="60.2" y2="12.6" href="#P">
                <stop stopColor="#3300d2" />
                <stop offset="1" stopColor="#ff52ff" />
            </linearGradient>
            <linearGradient id="g4" x1="519.4" y1="-222.4" x2="60.2" y2="12.6" href="#P">
                <stop stopColor="#3300d2" />
                <stop offset="1" stopColor="#ff52ff" />
            </linearGradient>
            <linearGradient id="g5" x1="605.1" y1="-211.2" x2="183.6" y2="4.4" href="#P">
                <stop stopColor="#3300d2" />
                <stop offset="1" stopColor="#ff52ff" />
            </linearGradient>
            <linearGradient id="g6" x1="139.4" y1="386.3" x2="491.6" y2="193.4" href="#P">
                <stop stopColor="#00e8d2" />
                <stop offset=".33" stopColor="#00add2" />
                <stop offset="1" stopColor="#4429ff" />
            </linearGradient>
            <linearGradient id="g7" x1="270.1" y1="458.2" x2="607.1" y2="255.9" href="#P">
                <stop stopColor="#00e8d2" />
                <stop offset=".33" stopColor="#00add2" />
                <stop offset="1" stopColor="#4429ff" />
            </linearGradient>
            <linearGradient id="g8" x1="701.5" y1="50" x2="966.7" y2="492.4" href="#P">
                <stop stopColor="#3300d2" />
                <stop offset="1" stopColor="#ff52ff" />
            </linearGradient>
            <linearGradient id="g9" x1="701.5" y1="50" x2="966.7" y2="492.4" href="#P">
                <stop stopColor="#3300d2" />
                <stop offset="1" stopColor="#ff52ff" />
            </linearGradient>
            <linearGradient id="g10" x1="622.9" y1="14.2" x2="866.3" y2="420.2" href="#P">
                <stop stopColor="#3300d2" />
                <stop offset="1" stopColor="#ff52ff" />
            </linearGradient>
        </defs>
        <path style={{fill: "url(#g1)"}} d="m363.8 108.4c-42.4 23.5-14.5 136.7 14.7 189.3l80.7 145.5c12.5 22.6 4.3 51-18.3 63.6l-40.9 22.7-22.7-41-80.7-145.4c-46.2-83.3-16-188.5 67.2-234.7z" />
        <path style={{fill: "url(#g2)"}} d="m296.6 343.1c-31-55.9-27.5-121.6 3.2-172.8-10.8 43.7 10.9 120.4 33.4 161l80.7 145.5c8.1 14.4 7.6 31.2 0.3 44.8l-14.2 7.9-22.7-41z" />
        <path style={{fill: "url(#g3)"}} d="m363.8 108.4l145.5-80.7 40.9-22.7 22.7 40.9c12.6 22.6 4.4 51.1-18.2 63.6l-145.5 80.7c-38.1 21.2-51.9 69.4-30.7 107.5-29.2-52.6-57.1-165.8-14.7-189.3z" />
        <path style={{fill:"url(#g4)"}} d="m363.8 108.4l145.5-80.7 40.9-22.7 22.7 40.9c12.6 22.6 4.4 51.1-18.2 63.6l-145.5 80.7c-38.1 21.2-51.9 69.4-30.7 107.5-29.2-52.6-57.1-165.8-14.7-189.3z" />
        <path style={{fill: "url(#g5)"}} d="m365.2 155.4l166.9-92.6 38.4-21.3 2.4 4.4c12.6 22.6 4.4 51.1-18.2 63.6l-145.5 80.7c-37.2 20.7-51.2 67.1-32.1 104.8q0 0-0.1 0c-14.7-27.5-28.7-69.8-33.3-107.7 3.7-14.5 10.5-25.9 21.5-31.9z" />
        <path style={{fill: "url(#g6)"}} d="m658.4 413c23.5 42.5-60.1 123.7-112.6 152.9l-145.5 80.7c-22.6 12.5-30.8 41-18.2 63.6l22.7 40.9 40.9-22.7 145.5-80.7c83.2-46.2 113.4-151.4 67.2-234.7z" />
        <path style={{fill: "url(#g7)"}} d="m591.2 647.7c55.8-31 87.7-88.5 88.7-148.2-13.9 42.8-73 96.3-113.5 118.8l-145.5 80.7c-14.5 8-23 22.6-24 37.9l7.9 14.2 40.9-22.7z" />
        <path style={{fill: "url(#g8)"}} d="m658.4 413l-80.7-145.4-22.7-41-40.9 22.7c-22.6 12.6-30.8 41.1-18.3 63.7l80.7 145.4c21.2 38.1 7.4 86.3-30.7 107.5 52.5-29.2 136.1-110.4 112.6-152.9z" />
        <path style={{fill: "url(#g9)"}} d="m658.4 413l-80.7-145.4-22.7-41-40.9 22.7c-22.6 12.6-30.8 41.1-18.3 63.7l80.7 145.4c21.2 38.1 7.4 86.3-30.7 107.5 52.5-29.2 136.1-110.4 112.6-152.9z" />
        <path style={{fill: "url(#g10)"}} d="m632.4 452.2l-92.6-166.9-21.3-38.4-4.4 2.4c-22.6 12.6-30.8 41.1-18.3 63.7l80.7 145.4c20.7 37.3 8 84.1-28.1 105.9q0 0.1 0 0.1c27-15.6 61.4-44 85.3-73.7 4.6-14.3 4.8-27.6-1.3-38.5z" />
    </svg>
);

registerPlugin('siteseo-sidebar', {
    render : sitseo_sidebar,
    icon : siteseo_sidebar_icon
})
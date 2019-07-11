import * as React from 'react';
import { render } from 'react-dom';

import './types/Window';

import App from './components/App';


const blogs: HTMLCollectionOf<HTMLElement> = document.getElementsByClassName('Microblog') as HTMLCollectionOf<HTMLElement>;

for (let i = 0, c = blogs.length; i < c; i++) {
    let blogElem: HTMLElement = blogs.item(i) as HTMLElement;
    let propertiesJson = blogElem.getAttribute('data-microblog-settings');
    if (!propertiesJson || propertiesJson.length == 0) {
        propertiesJson = "{}";
    }

    let properties = JSON.parse(propertiesJson);

    render(
        <App settings={properties} />
        ,
        blogElem
    )
}


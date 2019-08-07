import * as React from 'react';

interface Props {
    active: boolean
    colour?: string
}

const LoadingSpinner = ({ colour = '#fff', active }: Props): JSX.Element => {

    return (
        <div className="LoadingSpinner">
            {active && <div style={{ "background": colour }}></div>}
            {active && <div style={{ "background": colour }}></div>}
            {active && <div style={{ "background": colour }}></div>}
            {active && <div style={{ "background": colour }}></div>}
        </div>
    );
}

export default LoadingSpinner;
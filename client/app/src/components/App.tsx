import * as React from 'react';

import { Microblog } from './organisms/Microblog';

interface Props {
    classes?: any
    settings?: any
}

class App extends React.Component<Props> {

    public render(): JSX.Element {
        return (
            <Microblog settings={this.props.settings} />
        )
    }
};

export default App;
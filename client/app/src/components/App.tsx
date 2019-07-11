import * as React from 'react';

import Microblog from './organisms/Microblog';
import { Provider } from 'react-redux';
import store from 'src/store/Store';

interface Props {
    classes?: any
    settings?: any
}

class App extends React.Component<Props> {

    public render(): JSX.Element {
        return (
            <Provider store={store}>
            <Microblog settings={this.props.settings} />
            </Provider>
        )
    }
};

export default App;
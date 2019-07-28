import * as React from 'react';

import Microblog from './organisms/Microblog';
import { Provider } from 'react-redux';
import store from 'src/store/Store';
import { setUser } from 'src/microblog/actions/MicroBlogActions';

interface Props {
    classes?: any
    settings?: any
}

class App extends React.Component<Props> {

    componentDidMount(): void {
        // if we have user data
        const settings = this.props.settings;
        if (settings && settings.Member.ID) {
            store.dispatch(setUser(settings.Member));
        }
    }

    public render(): JSX.Element {
        return (
            <Provider store={store}>
            <Microblog settings={this.props.settings} />
            </Provider>
        )
    }
};

export default App;
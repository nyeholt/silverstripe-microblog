import * as React from 'react';
import { MicroPost } from 'src/microblog/type/MicroPost';
import MicroblogPostList from '../molecules/MicroblogPostList';
import MicroblogForm from '../molecules/MicroblogForm';
import { loadPosts } from 'src/microblog/actions/MicroBlogActions';
import { Dispatch, AnyAction } from 'redux';
import { GlobalStore } from 'src/type/GlobalStore';
import { MicroPostMap } from 'src/microblog/type/MicroPostMap';
import { connect } from 'react-redux';
import RemoteSourceDataManager from 'src/store/RemoteSourceDataManager';
import MicroPostDataSource from 'src/microblog/MicroPostDataSource';



type Props = OwnProps & StateProps & DispatchProps
interface OwnProps {
    settings: MicroblogSettings
}

interface StateProps {
    posts?: MicroPostMap
    loading: boolean
}

interface DispatchProps {
    loadPosts?: () => void
}

interface MicroblogSettings {
    apiKey: string
    member: MicroblogMember
    [key: string]: any
}

interface MicroblogMember {
    Name: string
    ID: string
}

interface State {
}

class Microblog extends React.Component<Props, State>  {

    constructor(props: Props) {
        super(props)
    }

    getFilter(type = 'Filter'): {[key: string]: string} {

        const { settings } = this.props;

        let filter = settings[type];
        if (!filter && settings['Filter']) {
            filter = settings['Filter']
        }
        if (!filter) {
            filter = {"ParentID": "0"} 
        }
        if (settings['Target']) {
            filter['Target'] = settings['Target'];
        }
        return filter;
    }

    getFilterString(type = 'FetchFilter'): string {
        let filter = this.getFilter(type);
        let items = [];
        for (var key in filter) {
            items.push(key + "=" + filter[key]);
        }
        return items.join(";");
    }

    componentDidMount(): void {
        // we're not worrying about filters just yet...
        RemoteSourceDataManager.registerDataSource(MicroPostDataSource(this.getFilterString()));

        // this.componentWillMount
        // wretch("/api/v1/microblog/posts")
        //     .get()
        //     .json((data) => {
        //         if (data && data.payload) {
        //             const loadedPosts: MicroPost[] = data.payload;

        //             this.setState(() => {
        //                 return {
        //                     loading: false,
        //                     posts: loadedPosts
        //                 }
        //             })
        //         }
        //     });
    }

    componentWillUnmount(): void {
        RemoteSourceDataManager.removeDataSource(this.getFilterString());
    }

    render(): JSX.Element {
        const {
            posts,
            settings,
            loading
        } = this.props;

        let actualFilter = this.getFilter();
        let orderedPosts: MicroPost[] = [];

        const singleView = settings.SingleView ? true : false;

        if (posts) {
            for (let i in posts) {
                const post = posts[i];
                if (actualFilter) {
                    let matched = true;
                    for (let field in actualFilter) {
                        if (post[field] != actualFilter[field]) {
                            matched = false;
                            // we only need _one_ field to not match and we can then
                            // break out
                            break;
                        }
                    }
                    if (matched) {
                        orderedPosts.push(post);
                    }
                } else {
                    orderedPosts.push(post);
                }
            }
        }

        const hasMember = settings.Member && settings.Member.ID;
        const target = settings.Target ? settings.Target : null;

        return (<div>
            {hasMember > 0 && !singleView && <MicroblogForm target={target} />}
            {loading && <div>Loading...</div>}
            <MicroblogPostList posts={orderedPosts} />
        </div>)
    }
}


const mapStateToProps = (state: GlobalStore): StateProps => {
    return {
        posts: state.microblog.posts,
        loading: state.microblog.postsLoading
    }
}

const mapDispatchToProps = (dispatch: Dispatch<AnyAction>): DispatchProps => {
    return {
        loadPosts: () => dispatch(loadPosts() as any),
    };
}

const ConnectedMicroblog = connect<StateProps & DispatchProps>(mapStateToProps, mapDispatchToProps)(Microblog);
export default ConnectedMicroblog;

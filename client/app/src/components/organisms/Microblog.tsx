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

    componentDidMount(): void {
        // we're not worrying about filters just yet...
        RemoteSourceDataManager.registerDataSource(MicroPostDataSource(""));

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
        RemoteSourceDataManager.removeDataSource("");
    }


    render(): JSX.Element {
        const {
            posts,
            settings,
            loading
        } = this.props;

        let orderedPosts: MicroPost[] = [];

        if (posts) {
            for (let i in posts) {
                orderedPosts.push(posts[i]);
            }

            orderedPosts.sort((a, b) => {
                return (a.ID == b.ID ? 0 : (
                    a.ID < b.ID ? 1 : -1
                ));
            })
        }

        const hasMember = settings.Member && settings.Member.ID;

        return (<div>
            {hasMember && <MicroblogForm />}
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

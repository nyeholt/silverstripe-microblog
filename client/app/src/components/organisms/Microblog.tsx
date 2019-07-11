import * as React from 'react';
import { MicroPost } from 'src/microblog/type/MicroPost';
import MicroblogPostList from '../molecules/MicroblogPostList';
import { MicroblogForm } from '../molecules/MicroblogForm';
import { loadPosts } from 'src/microblog/actions/MicroBlogActions';
import { Dispatch, AnyAction } from 'redux';
import { GlobalStore } from 'src/type/GlobalStore';
import { MicroPostMap } from 'src/microblog/type/MicroPostMap';
import { connect } from 'react-redux';


type Props = OwnProps & StateProps & DispatchProps
interface OwnProps {
    settings: MicroblogSettings

}

interface StateProps {
    posts?: MicroPostMap
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
    loading: boolean
}

class Microblog extends React.Component<Props, State>  {

    constructor(props: Props) {
        super(props)

        this.state = {
            loading: true
        }
    }

    componentDidMount(): void {
        this.props.loadPosts ? this.props.loadPosts() : null;
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


    render(): JSX.Element {
        const {
            posts
        } = this.props;

        let orderedPosts: MicroPost[] = [];

        if (posts) {
            for (let i in posts) {
                orderedPosts.push(posts[i]);
            }
        }

        return (<div>
            <MicroblogForm />
            {orderedPosts.length === 0 && <div>Loading...</div>}
            <MicroblogPostList posts={orderedPosts} />
        </div>)
    }
}


const mapStateToProps = (state: GlobalStore): StateProps => {
    return {
        posts: state.microblog.posts
    }
}


const mapDispatchToProps = (dispatch: Dispatch<AnyAction>): DispatchProps => {
    return {
        loadPosts: () => dispatch(loadPosts() as any),
    };
}


const ConnectedMicroblog = connect<StateProps & DispatchProps>(mapStateToProps, mapDispatchToProps)(Microblog);
export default ConnectedMicroblog;

import * as React from 'react';
import wretch from 'wretch';
import { MicroPost } from 'src/microblog/type/MicroPost';
import MicroblogPostList from '../molecules/MicroblogPostList';

interface Props {
    settings: MicroblogSettings
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
    posts: MicroPost[]
}

export class Microblog extends React.Component<Props, State>  {

    constructor(props: Props) {
        super(props)

        this.state = {
            loading: true,
            posts: []
        }
    }

    componentDidMount(): void {
        wretch("/api/v1/microblog/posts")
            .get()
            .json((data) => {
                if (data && data.payload) {
                    const loadedPosts: MicroPost[] = data.payload;

                    this.setState(() => {
                        return {
                            loading: false,
                            posts: loadedPosts
                        }
                    })
                }
            });
    }


    render(): JSX.Element {
        const {
            loading,
            posts
        } = this.state;

        return (<div>

            {loading && <div>Loading...</div>}
            <MicroblogPostList posts={posts} />
        </div>)
    }
}
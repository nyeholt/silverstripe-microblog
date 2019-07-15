import * as React from 'react';
import { MicroPost } from 'src/microblog/type/MicroPost';

interface Props {
    posts: MicroPost[]
}

const MicroblogPostList = ({ posts }: Props): JSX.Element => {
    return (
        <div className="MicroblogPostList">
            {posts.map((post) => {
                return <div className="Card" key={post.ID}>
                    <div className="Card__Title">{post.Title}</div>
                    <div className="Card__Body">{post.Content}</div>
                    <div className="Card__Actions">

                    </div>
                </div>
            })}
        </div>
    );
}

export default MicroblogPostList;
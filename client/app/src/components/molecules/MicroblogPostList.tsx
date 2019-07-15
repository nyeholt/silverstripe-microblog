import * as React from 'react';
import { MicroPost } from 'src/microblog/type/MicroPost';
import MicroBlogPost from './MicroBlogPost';

interface Props {
    posts: MicroPost[]
}

const MicroblogPostList = ({ posts }: Props): JSX.Element => {
    return (
        <div className="MicroblogPostList">
            {posts.map((post) => {
                return <MicroBlogPost post={post}  key={post.ID} />
            })}
        </div>
    );
}

export default MicroblogPostList;
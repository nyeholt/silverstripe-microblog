import * as React from 'react';
import { MicroPost } from 'src/microblog/type/MicroPost';
import MicroBlogPost from './MicroBlogPost';

interface Props {
    posts: MicroPost[]
    expectedCount?: number
    parentId?: string
    loadChildren?: () => void
}

const MicroblogPostList = ({ posts, expectedCount, loadChildren }: Props): JSX.Element => {
    const loadMore = expectedCount && expectedCount > 0 && posts.length < expectedCount;
    posts.sort((a, b) => {
        return (a.ID == b.ID ? 0 : (
            a.ID < b.ID ? 1 : -1
        ));
    });
    return (
        <div className="MicroblogPostList">
            {posts.map((post) => {
                return <MicroBlogPost post={post}  key={post.ID} />
            })}
            {loadMore && <div className="MicroblogPostList__LoadMore"><a href="#" onClick={(e: React.SyntheticEvent) => { e.preventDefault(); loadChildren ? loadChildren() : null; }}>Show replies...</a></div> }
        </div>
    );
}

export default MicroblogPostList;


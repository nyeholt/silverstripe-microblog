import React from 'react';
import { storiesOf } from '@storybook/react';
import { action } from '@storybook/addon-actions';
import Button from 'src/components/Button';

import { HeaderNotifications } from 'src/components/organisms/HeaderNotifications';

storiesOf('Button', module)
  .add('with text', () => (
    <Button onClick={action('clicked')}>Hello Button</Button>
  ))
  .add('with some emoji', () => (
    <Button onClick={action('clicked')}><span role="img" aria-label="so cool">😀 😎 👍 💯</span></Button>
  ));   


storiesOf ('HeaderNotifications', module)
    .add('no notifications', () => (
        <HeaderNotifications username="noone" profileLink="" />
    ));
# Symbiote webpack base

A base webpack configuration containing

* Webpack 4 configs for
  * `yarn devserver` - runs the webpack dev server on localhost:4200
  * `yarn watch` - builds files to www/, but does _not_ start devserver; 
    expects a separate webserver to include files. Runs a watch so that 
    the browser will reload on file change
  * `yarn build` - produces production build outputs using the same output 
    paths as `yarn watch` for external project inclusion
  * `yarn storybook` - runs stories from /app/stories
* React 16
* Redux


## Getting started

Clone this repo

Run `./du.sh` which will start a node container

Run `./dr.sh yarn install`, then `./dr.sh yarn devserver`


## Redux / Redux thunk

> If you do _not_ require redux, you can remove the imports and
> references from src/components/App.tsx, delete the store/ folder,
> and remove the examplemodule. 

State management is handled using Redux, with Redux thunk providing async
data loading capabilities. 

To utilise in your project, you will need to 

* Update GlobalStore to remove the example module and bind your own types in
* Add to store/type/Actions with your own redux action labels
* Define your own action creators and reducers - see the example module for 
  one method of doing so 
* Update store/Store.ts to load _your_ reducer(s) and remove the example
* Update components/App 
'use strict';

import {Component} from 'preact';
import BookCategoryView from './book-category-view';

export default class BookCategoryViewApp extends Component {
  render(props, state, context) {
    return (
        <BookCategoryView style={{position: 'relative'}}
                          category={props.category}
                          categories={props.categories}
                          upcoming={props.upcoming}/>
    );
  }

}
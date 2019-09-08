'use strict';

import {Component} from 'preact';
import BookCategoryLeftSidebar from './book-category-left-sidebar';
import BookListView from './book-list-view';
import styles from './styles/category-view.scss';

export default class BookCategoryView extends Component {
  constructor() {
    super();

    this.state.searchObj = {
      title: '',
      author: '',
    };
    this.changeSearchObj = this.changeSearchObj.bind(this);
  }

  changeSearchObj(searchObj) {
    this.setState({
      searchObj: {
        title: searchObj.title,
        authors: searchObj.authors,
      },
    });
  }

  render(props, state, context) {
    const {categories, category} = props;
    const {searchObj} = state;
    return (
        <div class={styles.categoryView}>
          <BookCategoryLeftSidebar categories={categories}
                                   onSearch={this.changeSearchObj}/>
          <BookListView category={category} search={searchObj}/>
        </div>
    );
  }
}
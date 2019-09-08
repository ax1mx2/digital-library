'use strict';

import {Component} from 'preact';
import style from './styles/left-sidebar.scss';
import BookCategoryListElem from './book-category-list-elem';

export default class BookCategoryLeftSidebar extends Component {
  constructor() {
    super();

    this.state = {
      title: '',
      authors: '',
    };

    this.search = this.search.bind(this);
    this.setTitle = this.setTitle.bind(this);
    this.setAuthors = this.setAuthors.bind(this);
  }

  setTitle({target: {value}}) {
    this.setState({title: value});
  }

  setAuthors({target: {value}}) {
    this.setState({authors: value});
  }

  search(event) {
    event.preventDefault();

    if (typeof this.props.onSearch === 'function') {
      this.props.onSearch({
        title: this.state.title,
        authors: this.state.authors,
      });
    }
  }

  render(props, state, context) {
    const {categories} = props;
    const categoryListElems =
        Array.isArray(categories) && categories.length > 0
            ? (
                <div>
                  {categories.map((el) => <BookCategoryListElem name={el.name}
                                                                childCategories={el.childCategories}/>)}
                </div>
            ) : null;

    return (
        <div className={style.leftSidebar}>
          <h4>{window.dl_data.search || 'Search'}</h4>
          <form onSubmit={this.search}>
            <input type="text" value={state.title} onInput={this.setTitle}
                   placeholder={window.dl_data.titleSearch || 'Title...'}/>
            <input type="text" value={state.authors} onInput={this.setAuthors}
                   placeholder={window.dl_data.authorsSearch || 'Authors...'}/>
            <button type="submit">
              {window.dl_data.applySearch || 'Apply'}
            </button>
          </form>
          <h4>{window.dl_data.categories || 'Categories'}</h4>
          {categoryListElems}
        </div>
    );
  }
}
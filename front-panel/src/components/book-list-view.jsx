'use strict';

import {Component} from 'preact';
import React from 'react';
import styles from './styles/list-view.scss';
import {fetchBooks} from '../services/search';
import BookListViewItem from './book-list-view-item';
import Loading from './loading';
import Spinner from './spinner';

import Observer from '@researchgate/react-intersection-observer';

export default class BookListView extends Component {
  constructor() {
    super();

    this.state = this.initState();

    this.shouldLoadMoreBooks = this.shouldLoadMoreBooks.bind(this);
    this.loadMoreBooks = this.loadMoreBooks.bind(this);
  }

  initState() {
    return {
      loading: true,
      books: [],
      page: 0,
      noMore: false,
    };
  }

  componentWillMount() {
    this.loadMoreBooks();
  }

  componentWillReceiveProps(nextProps, nextContext) {
    if (typeof nextProps.search === 'object') {
      const {title: newTitle, authors: newAuthors} = nextProps.search;
      const search = this.props.search || {};
      const {title, authors} = search;
      // Reset state and load more books, if searching.
      if (title !== newTitle || authors !== newAuthors) {
        this.setState(this.initState(), () => {
          this.loadMoreBooks();
        });
      }
    } else {
      this.loadMoreBooks();
    }
  }

  shouldLoadMoreBooks(event) {
    if (this.state.loading) {
      return;
    }
    // Load more if last line is visible.
    if (event.intersectionRatio > 0) {
      this.loadMoreBooks();
    }
  }

  loadMoreBooks() {
    let title = null, authors = null;
    if (typeof this.props.search === 'object') {
      title = this.props.search.title;
      authors = this.props.search.authors;
    }

    this.setState({loading: true, page: this.state.page + 1}, () => {
      fetchBooks(this.props.category, title, authors, this.state.page).
          then(res => {
            this.setState({
              loading: false,
              books: [...this.state.books, ...res.books],
              noMore: res.pages === this.state.page,
            });
          });
    });
  }

  render(props, {page, books, loading, noMore}, context) {
    const loadingElem = Loading({visible: loading && page === 1});

    let bookElems;

    if (Array.isArray(books)) {
      if (books.length > 0) {
        const loadMoreElem = noMore ? null : (
            <div style={{
              flex: '1',
              minWidth: '100%',
              height: '3em',
              fontSize: '2em',
              textAlign: 'center',
            }}>
              <Observer onChange={this.shouldLoadMoreBooks}>
                <div>
                  <Spinner/>
                  <div style={{
                    display: 'inline-block',
                    lineHeight: '2em',
                    paddingLeft: '1em',
                    verticalAlign: 'top',
                  }}>
                    {window.dl_data.loadingMoreBooks || 'Loading more books...'}
                  </div>
                </div>
              </Observer>
            </div>
        );
        bookElems = (
            <div class={styles.booksList}>
              {books.map(BookListViewItem)}
              {loadMoreElem}
            </div>
        );
      } else if (!loading) {
        bookElems =
            <h3>{window.dl_data.noBooksAvailable || 'No books available.'}</h3>;
      }
    }

    return (
        <div className={styles.listView}>
          <h3>{window.dl_data.books || 'Books'}</h3>
          {loadingElem}
          {bookElems}
        </div>
    );
  }
}
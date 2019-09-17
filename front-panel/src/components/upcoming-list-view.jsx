'use strict';

import {Component} from 'preact';
import UpcomingBookListItem from './upcoming-book-list-item';
import styles from './styles/upcoming-list-view.scss';

export class UpcomingListView extends Component {

  render({upcoming}, state, context) {
    const bookElems = upcoming.map(UpcomingBookListItem);
    return (
        <div class={styles.upcomingBooksListView}>
          <h3 style={{textAlign: 'center'}}>
            {window.dl_data.upcoming || 'Upcoming'}
          </h3>
          <div class={styles.upcomingBooksList}>
            {bookElems}
          </div>
        </div>
    );
  }

}
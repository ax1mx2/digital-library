'use strict';

import styles from './styles/loading.scss';

export default function Loading({visible}) {
  if (visible) {
    return (
        <div class={styles.loading}>
          <div class={styles.loadingText}>
            {window.dl_data.loading || 'Loading...'}
          </div>
        </div>
    );
  }
  return null;
}
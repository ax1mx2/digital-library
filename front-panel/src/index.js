import habitat from 'preact-habitat';
import BookCategoryViewApp from './components/book-cateogory-view-app';

const appWrapper = habitat(BookCategoryViewApp);

appWrapper.render({
  selector: '.book-category-view-app',
  clean: true,
});

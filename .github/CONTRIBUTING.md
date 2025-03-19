# Contribution Guide

Thank you for your interest in our project! I'm glad that you want to help make it better. This guide will help you understand how you can contribute.

## How to Get Started?

1. **Fork the Repository**: Create your own copy of the repository by clicking the "Fork" button in the top right corner of the page.
2. **Clone the Repository**:
   ```
   git clone https://github.com/your-username/your-repo.git
   cd your-repo
   ```
3. **Set Up the Environment**:
   - Make sure you have the required dependencies installed (e.g., PHP 8.1+ and Composer).
   - Run the command to install dependencies:
     ```
     composer install
     ```

## How to Make Changes?

1. Create a new branch for your changes:
   ```
   git checkout -b feature/your-feature-name
   ```
2. Make your changes to the code.
3. Ensure your code adheres to the project standards (see the "Code Style Guidelines" section).
4. Run the tests, if available:
   ```
   ./vendor/bin/phpunit
   ```

## How to Submit a Pull Request?

1. Commit your changes:
   ```
   git add .
   git commit -m "feat: Description of your changes"
   ```
2. Push your changes to your fork:
   ```
   git push origin feature/your-feature-name
   ```
3. Go to the original repository and create a pull request from your branch.
4. Ensure your pull request includes:
   - A clear description of the changes.
   - Information on how these changes affect the project.

## Code Style Guidelines

The project adheres to the following standards:
- Use [PER Code Style 2](https://www.php-fig.org/per/coding-style/) for code formatting.
- Add comments to complex parts of the code.
- Write tests for new functionality.

## Review Process

1. After creating a pull request, I will review your changes.
2. If any changes are needed, I will leave comments. Please be ready to discuss them.
3. Once approved, the changes will be merged into the main branch.

## Contacts

If you have questions or are unsure how to make changes:
- Create an issue with the label `question`.

Thank you for your contribution!
import { Component } from 'react'
import type { ErrorInfo, ReactNode } from 'react'

type Props = { children: ReactNode }
type State = { error: Error | null }

export class ErrorBoundary extends Component<Props, State> {
  public state: State = { error: null }

  public static getDerivedStateFromError(error: Error): State {
    return { error }
  }

  public componentDidCatch(error: Error, errorInfo: ErrorInfo): void {
    console.error('React application error', error, errorInfo)
  }

  public render(): ReactNode {
    if (this.state.error !== null) {
      return <main className="app-error"><h1>Something went wrong</h1><p>{this.state.error.message}</p><button onClick={() => this.setState({ error: null })}>Try again</button></main>
    }

    return this.props.children
  }
}
